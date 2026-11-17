<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use App\Services\FileStore;
use App\Services\Notifier;
use App\Support\Calendar;
use App\Support\Labels;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function index(Request $request, Brand $brand)
    {
        $month = Calendar::parseMonth($request->query('month'));
        $view = in_array($request->query('view'), ['calendar', 'list']) ? $request->query('view') : 'calendar';
        $posts = $brand->posts()->with('attachments')
            ->whereBetween('publish_date', [$month->copy()->startOfMonth()->startOfWeek(), $month->copy()->endOfMonth()->endOfWeek()])
            ->orderBy('publish_date')->orderBy('publish_time')->get();

        $counts = $posts->filter(fn ($p) => $p->publish_date->month === $month->month)->countBy('status');

        return view('posts.index', [
            'brand' => $brand,
            'month' => $month,
            'view' => $view,
            'byDate' => Calendar::group($posts),
            'counts' => $counts,
        ]);
    }

    public function create(Request $request, Brand $brand)
    {
        $post = new Post([
            'publish_date' => $request->query('date', today()->toDateString()),
            'type' => 'post',
            'platforms' => ['instagram', 'facebook'],
        ]);

        return view('posts.form', compact('brand', 'post'));
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'publish_date' => ['required', 'date'],
            'publish_time' => ['nullable', 'date_format:H:i'],
            'type' => ['required', Rule::in(array_keys(Labels::POST_TYPES))],
            'platforms' => ['array'],
            'platforms.*' => [Rule::in(array_keys(Labels::PLATFORMS))],
            'caption' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachment_ids' => ['array'],
            'action' => ['nullable', Rule::in(['draft', 'submit', 'send'])],
        ];
    }

    public function store(Request $request, Brand $brand)
    {
        $data = $request->validate($this->rules());
        $post = $brand->posts()->create([
            ...collect($data)->only(['title', 'publish_date', 'publish_time', 'type', 'platforms', 'caption', 'notes'])->all(),
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);
        FileStore::claim($data['attachment_ids'] ?? [], $request->user(), $post, ['brand_id' => $brand->id]);
        Activity::log("“{$post->title}” paylaşımını əlavə etdi (".Labels::shortDate($post->publish_date).')', $post);

        $this->applyAction($post, $data['action'] ?? 'draft', $request->user());

        return redirect()->route('posts.show', $post)->with('success', 'Paylaşım yadda saxlanıldı.');
    }

    private function applyAction(Post $post, string $action, User $user): void
    {
        if ($action === 'submit') {
            $this->doSubmit($post);
        } elseif ($action === 'send' && $user->hasPerm('approve')) {
            $this->doApprove($post);
        }
    }

    public function show(Post $post)
    {
        $post->load(['brand', 'attachments', 'comments.user', 'creator']);

        return view('posts.show', ['post' => $post, 'brand' => $post->brand]);
    }

    public function edit(Post $post)
    {
        $post->load('attachments');

        return view('posts.form', ['post' => $post, 'brand' => $post->brand]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $request->validate($this->rules());
        $post->fill(collect($data)->only(['title', 'publish_date', 'publish_time', 'type', 'platforms', 'caption', 'notes'])->all());
        $changed = $post->isDirty(['title', 'publish_date', 'caption', 'type']);
        // Müştəriyə getmiş paylaşım dəyişdirilirsə, yenidən təsdiq tələb olunur
        if ($changed && in_array($post->status, ['sent', 'client_approved'])) {
            $post->status = 'draft';
        }
        $post->save();
        $n = FileStore::claim($data['attachment_ids'] ?? [], $request->user(), $post, ['brand_id' => $post->brand_id]);
        if ($n && in_array($post->status, ['sent', 'client_approved'])) {
            $post->update(['status' => 'draft']);
        }
        $this->applyAction($post, $data['action'] ?? 'draft', $request->user());

        return redirect()->route('posts.show', $post)->with('success', 'Paylaşım yeniləndi.');
    }

    public function destroy(Post $post)
    {
        $brand = $post->brand;
        foreach ($post->attachments as $a) {
            FileStore::delete($a);
        }
        $post->delete();

        return redirect()->route('brands.social', [$brand, 'month' => $post->publish_date->format('Y-m')])->with('success', 'Paylaşım silindi.');
    }

    public function files(Request $request, Post $post)
    {
        $data = $request->validate(['attachment_ids' => ['required', 'array']]);
        FileStore::claim($data['attachment_ids'], $request->user(), $post, ['brand_id' => $post->brand_id]);
        if (in_array($post->status, ['sent', 'client_approved'])) {
            $post->update(['status' => 'draft']);

            return back()->with('success', 'Vizual əlavə edildi. Paylaşım yenidən təsdiq tələb edir.');
        }

        return back()->with('success', 'Vizual əlavə edildi.');
    }

    public function submit(Post $post)
    {
        abort_unless(in_array($post->status, ['draft', 'internal_changes', 'client_changes']), 422);
        $this->doSubmit($post);

        return back()->with('success', 'Paylaşım rəhbər təsdiqinə göndərildi.');
    }

    private function doSubmit(Post $post): void
    {
        $post->update(['status' => 'pending']);
        Activity::log("“{$post->title}” paylaşımını təsdiqə göndərdi", $post);
        Notifier::send(User::approvers(), 'Təsdiq gözləyir: '.$post->title,
            auth()->user()->name.' paylaşımı təsdiqinizə göndərdi ('.Labels::date($post->publish_date).').',
            route('posts.show', $post), $post->brand->name);
    }

    /** Rəhbər təsdiqi → birbaşa müştəri panelinə düşür */
    public function approve(Post $post)
    {
        abort_if(in_array($post->status, ['sent', 'client_approved', 'published']), 422, 'Bu paylaşım artıq müştəriyə göndərilib.');
        $this->doApprove($post);

        return back()->with('success', 'Təsdiqləndi və müştəriyə göndərildi.');
    }

    private function doApprove(Post $post): void
    {
        $post->update([
            'status' => 'sent',
            'reviewed_by' => auth()->id(),
            'sent_at' => now(),
            'last_reminder_at' => now(),
            'reminder_count' => 0,
            'client_decided_at' => null,
            'client_decided_by' => null,
        ]);
        Activity::log("“{$post->title}” paylaşımını təsdiqləyib müştəriyə göndərdi", $post);
        Notifier::send(User::clientUsersOf($post->brand), 'Yeni paylaşım təsdiqinizi gözləyir',
            "“{$post->title}” — paylaşım tarixi: ".Labels::date($post->publish_date).'. Zəhmət olmasa baxıb təsdiqləyin və ya rəy bildirin.',
            route('portal.posts.show', $post), $post->brand->name);
        if ($post->creator && $post->created_by !== auth()->id()) {
            Notifier::send([$post->creator], 'Paylaşımınız təsdiqləndi: '.$post->title,
                'Rəhbər təsdiqlədi və paylaşım müştəriyə göndərildi.', route('posts.show', $post), $post->brand->name);
        }
    }

    public function reject(Request $request, Post $post)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $post->update(['status' => 'internal_changes', 'reviewed_by' => $request->user()->id]);
        $post->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body'], 'visibility' => 'internal']);
        Activity::log("“{$post->title}” paylaşımına düzəliş istədi", $post);
        Notifier::send(array_filter([$post->creator]), 'Düzəliş lazımdır: '.$post->title,
            $request->user()->name.': '.str($data['body'])->limit(200), route('posts.show', $post), $post->brand->name);

        return back()->with('success', 'Düzəliş tələbi göndərildi.');
    }

    public function published(Post $post)
    {
        abort_unless($post->status === 'client_approved', 422, 'Yalnız müştərinin təsdiqlədiyi paylaşım "paylaşıldı" edilə bilər.');
        $post->update(['status' => 'published']);
        Activity::log("“{$post->title}” paylaşıldı kimi qeyd etdi", $post);

        return back()->with('success', 'Paylaşıldı kimi qeyd edildi.');
    }

    public function comment(Request $request, Post $post)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $visibility = $request->boolean('to_client') && $post->isClientVisible() ? 'client' : 'internal';
        $post->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body'], 'visibility' => $visibility]);
        if ($visibility === 'client') {
            Notifier::send(User::clientUsersOf($post->brand), 'Agentlikdən mesaj: '.$post->title,
                str($data['body'])->limit(300), route('portal.posts.show', $post), $post->brand->name);
        } elseif ($post->creator) {
            Notifier::send([$post->creator], 'Yeni şərh: '.$post->title,
                $request->user()->name.': '.str($data['body'])->limit(200), route('posts.show', $post), $post->brand->name);
        }

        return back();
    }

    /** İşçi əl ilə müştəriyə xatırlatma göndərir */
    public function remind(Post $post)
    {
        abort_unless($post->status === 'sent', 422);
        Notifier::send(User::clientUsersOf($post->brand), 'Xatırlatma: paylaşım təsdiqinizi gözləyir',
            "“{$post->title}” (".Labels::date($post->publish_date).') hələ təsdiqlənməyib.', route('portal.posts.show', $post), $post->brand->name);
        $post->update(['last_reminder_at' => now(), 'reminder_count' => $post->reminder_count + 1]);

        return back()->with('success', 'Müştəriyə xatırlatma göndərildi.');
    }

    public function remindAll(Brand $brand)
    {
        $posts = $brand->posts()->where('status', 'sent')->orderBy('publish_date')->get();
        if ($posts->isEmpty()) {
            return back()->with('success', 'Təsdiq gözləyən paylaşım yoxdur.');
        }
        $list = $posts->map(fn ($p) => Labels::shortDate($p->publish_date).' — '.$p->title)->implode('; ');
        Notifier::send(User::clientUsersOf($brand), "Xatırlatma: {$posts->count()} paylaşım təsdiqinizi gözləyir", $list, route('portal.posts', ['view' => 'list']), $brand->name);
        Post::whereIn('id', $posts->pluck('id'))->update(['last_reminder_at' => now()]);

        return back()->with('success', 'Müştəriyə xatırlatma göndərildi.');
    }
}
