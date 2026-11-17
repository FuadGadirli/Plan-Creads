<?php

namespace App\Http\Controllers\Portal;

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use App\Services\Notifier;
use App\Support\Calendar;
use App\Support\Labels;
use Illuminate\Http\Request;

class PostController extends PortalController
{
    public function index(Request $request)
    {
        $brand = $this->brand($request);
        $month = Calendar::parseMonth($request->query('month'));
        $view = $request->query('view') === 'list' ? 'list' : 'calendar';
        $posts = $brand->posts()->clientVisible()->with('attachments')
            ->whereBetween('publish_date', [$month->copy()->startOfMonth()->startOfWeek(), $month->copy()->endOfMonth()->endOfWeek()])
            ->orderBy('publish_date')->orderBy('publish_time')->get();
        $awaiting = $brand->posts()->where('status', 'sent')->count();

        return view('portal.posts.index', [
            'brand' => $brand, 'month' => $month, 'view' => $view,
            'byDate' => Calendar::group($posts), 'awaiting' => $awaiting,
        ]);
    }

    private function post(Request $request, Post $post): Post
    {
        $this->ensureBrand($request, $post->brand_id);
        $this->brand($request);
        abort_unless($post->isClientVisible(), 404);

        return $post;
    }

    public function show(Request $request, Post $post)
    {
        $post = $this->post($request, $post);
        $post->load('attachments');
        $comments = $post->comments()->with('user')->where('visibility', 'client')->get();

        return view('portal.posts.show', compact('post', 'comments'));
    }

    private function team(Post $post)
    {
        return User::approvers()->push($post->creator)->filter();
    }

    public function approve(Request $request, Post $post)
    {
        $post = $this->post($request, $post);
        abort_unless($post->status === 'sent', 422, 'Bu paylaşım üzrə artıq qərar verilib.');
        $post->update(['status' => 'client_approved', 'client_decided_at' => now(), 'client_decided_by' => $request->user()->id]);
        Activity::log("Müştəri “{$post->title}” paylaşımını təsdiqlədi", $post);
        Notifier::send($this->team($post), '✅ Müştəri təsdiqlədi: '.$post->title,
            $request->user()->name.' paylaşımı təsdiqlədi ('.Labels::date($post->publish_date).').', route('posts.show', $post), $post->brand->name);

        return back()->with('success', 'Paylaşım təsdiqləndi.');
    }

    public function feedback(Request $request, Post $post)
    {
        $post = $this->post($request, $post);
        abort_unless($post->status === 'sent', 422, 'Bu paylaşım üzrə artıq qərar verilib.');
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $post->update(['status' => 'client_changes', 'client_decided_at' => now(), 'client_decided_by' => $request->user()->id]);
        $post->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body'], 'visibility' => 'client']);
        Activity::log("Müştəri “{$post->title}” paylaşımına rəy bildirdi", $post);
        Notifier::send($this->team($post), '✏️ Müştəri rəyi: '.$post->title,
            $request->user()->name.': '.str($data['body'])->limit(300), route('posts.show', $post), $post->brand->name);

        return back()->with('success', 'Rəyiniz göndərildi.');
    }

    public function approveAll(Request $request)
    {
        $brand = $this->brand($request);
        $posts = $brand->posts()->where('status', 'sent')->get();
        foreach ($posts as $post) {
            $post->update(['status' => 'client_approved', 'client_decided_at' => now(), 'client_decided_by' => $request->user()->id]);
        }
        if ($posts->isNotEmpty()) {
            Activity::log("Müştəri {$posts->count()} paylaşımı birlikdə təsdiqlədi", null, $brand->id);
            Notifier::send(User::approvers(), "✅ Müştəri {$posts->count()} paylaşımı təsdiqlədi",
                $request->user()->name.' gözləyən bütün paylaşımları təsdiqlədi.', route('brands.social', $brand), $brand->name);
        }

        return back()->with('success', $posts->count().' paylaşım təsdiqləndi.');
    }
}
