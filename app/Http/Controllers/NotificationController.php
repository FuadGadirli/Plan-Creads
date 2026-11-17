<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(30);

        return view($request->user()->isClient() ? 'portal.notifications' : 'notifications', compact('notifications'));
    }

    public function count(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'items' => $user->unreadNotifications()->limit(6)->get()->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? '',
                'body' => $n->data['body'] ?? '',
                'brand' => $n->data['brand'] ?? null,
                'time' => $n->created_at->diffForHumans(),
                'url' => route('notifications.open', $n->id),
            ]),
        ]);
    }

    public function open(Request $request, string $id)
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        $path = (string) ($n->data['url'] ?? '/home');
        // Yalnız platformanın daxili ünvanlarına yönləndiririk
        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            $path = '/home';
        }

        return redirect()->to(url($path));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
