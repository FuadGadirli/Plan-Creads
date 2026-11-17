<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Brand;
use App\Models\ContentPlanVersion;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tasks = Task::query()->visibleTo($user);

        $stats = [
            'active' => (clone $tasks)->where('status', '!=', 'done')->count(),
            'overdue' => (clone $tasks)->where('status', '!=', 'done')->whereDate('due_date', '<', today())->count(),
            'week' => (clone $tasks)->where('status', '!=', 'done')->whereBetween('due_date', [today(), today()->addDays(7)])->count(),
            'done_month' => (clone $tasks)->where('status', 'done')->where('completed_at', '>=', now()->startOfMonth())->count(),
            'awaiting_client_posts' => Post::where('status', 'sent')->count(),
            'awaiting_client_plans' => ContentPlanVersion::where('status', 'sent')->count(),
            'pending_internal' => Post::where('status', 'pending')->count() + ContentPlanVersion::where('status', 'pending')->count(),
            'client_changes' => Post::where('status', 'client_changes')->count() + ContentPlanVersion::where('status', 'client_changes')->count(),
        ];

        $byStatus = (clone $tasks)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $myTasks = Task::involving($user)->where('status', '!=', 'done')
            ->with(['brand', 'users'])
            ->orderByRaw('due_date is null')->orderBy('due_date')
            ->limit(8)->get();

        $workload = $user->hasPerm('tasks.all')
            ? User::staff()->active()->withCount(['tasks as open_tasks' => fn ($q) => $q->where('status', '!=', 'done')])
                ->orderByDesc('open_tasks')->get()
            : collect();

        $upcomingPosts = $user->hasPerm('brands.social')
            ? Post::with('brand')->whereBetween('publish_date', [today(), today()->addDays(7)])
                ->orderBy('publish_date')->limit(8)->get()
            : collect();

        $brands = $user->hasPerm('brands')
            ? Brand::where('is_active', true)->withCount(['tasks as open_tasks' => fn ($q) => $q->where('status', '!=', 'done')])
                ->orderByDesc('open_tasks')->limit(6)->get()
            : collect();

        $activities = Activity::with(['user', 'brand'])->latest()->limit(10)->get();

        return view('dashboard', compact('stats', 'byStatus', 'myTasks', 'workload', 'upcomingPosts', 'brands', 'activities'));
    }
}
