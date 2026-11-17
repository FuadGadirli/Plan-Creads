<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Brand;
use App\Models\Task;
use App\Models\User;
use App\Services\FileStore;
use App\Services\Notifier;
use App\Support\Labels;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        return $this->listing($request, Task::visibleTo($request->user()), 'Tapşırıqlar');
    }

    public function mine(Request $request)
    {
        return $this->listing($request, Task::involving($request->user()), 'Mənim işlərim', mine: true);
    }

    public function brand(Request $request, Brand $brand)
    {
        return $this->listing($request, Task::visibleTo($request->user())->where('brand_id', $brand->id), $brand->name.' — Tapşırıqlar', brand: $brand);
    }

    private function listing(Request $request, $query, string $title, ?Brand $brand = null, bool $mine = false)
    {
        $view = $request->query('view', session('tasks.view', 'list'));
        if (! in_array($view, ['list', 'kanban'])) {
            $view = 'list';
        }
        session(['tasks.view' => $view]);

        $f = $request->only(['q', 'brand', 'status', 'user', 'priority', 'done']);

        $query->with(['brand', 'users', 'creator'])->withCount(['comments', 'attachments'])
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when(! $brand && ($f['brand'] ?? null), fn ($q) => $q->where('brand_id', $f['brand']))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['priority'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->when($f['user'] ?? null, fn ($q, $v) => $q->whereHas('users', fn ($u) => $u->where('users.id', $v)));

        // Siyahıda bitmiş tapşırıqlar default olaraq gizlədilir
        if ($view === 'list' && empty($f['status']) && empty($f['done'])) {
            $query->where('status', '!=', 'done');
        }

        $tasks = $query->orderBy('position')->orderByRaw('due_date is null')->orderBy('due_date')->latest('id')->get();

        return view('tasks.index', [
            'tasks' => $tasks,
            'title' => $title,
            'view' => $view,
            'filters' => $f,
            'brand' => $brand,
            'mine' => $mine,
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'staff' => User::staff()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request)
    {
        return view('tasks.form', [
            'task' => new Task(['brand_id' => $request->query('brand'), 'priority' => 'normal', 'status' => 'todo']),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'staff' => User::staff()->active()->orderBy('name')->get(),
            'assigned' => ['responsible' => [], 'supporter' => [], 'watcher' => []],
        ]);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'status' => ['required', Rule::in(array_keys(Labels::TASK_STATUSES))],
            'priority' => ['required', Rule::in(array_keys(Labels::PRIORITIES))],
            'due_date' => ['nullable', 'date'],
            'responsible' => ['array'],
            'responsible.*' => ['exists:users,id'],
            'supporter' => ['array'],
            'supporter.*' => ['exists:users,id'],
            'watcher' => ['array'],
            'watcher.*' => ['exists:users,id'],
            'attachment_ids' => ['array'],
        ];
    }

    private function syncPeople(Task $task, array $data): array
    {
        $before = $task->users()->pluck('users.id')->all();
        $sync = [];
        foreach (['watcher', 'supporter', 'responsible'] as $role) {
            foreach ($data[$role] ?? [] as $id) {
                $sync[(int) $id] = ['role' => $role];
            }
        }
        // Yalnız işçilər təyin oluna bilər
        $valid = User::staff()->whereIn('id', array_keys($sync))->pluck('id')->all();
        $sync = array_intersect_key($sync, array_flip($valid));
        $task->users()->sync($sync);

        return array_diff(array_keys($sync), $before);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $task = Task::create([
            ...collect($data)->only(['title', 'description', 'brand_id', 'status', 'priority', 'due_date'])->all(),
            'created_by' => $request->user()->id,
            'completed_at' => $data['status'] === 'done' ? now() : null,
        ]);
        $new = $this->syncPeople($task, $data);
        FileStore::claim($data['attachment_ids'] ?? [], $request->user(), $task, ['brand_id' => $task->brand_id]);

        Activity::log("“{$task->title}” tapşırığını yaratdı", $task);
        $this->notifyAssigned($task, $new);

        return redirect()->route('tasks.show', $task)->with('success', 'Tapşırıq yaradıldı.');
    }

    private function notifyAssigned(Task $task, array $userIds): void
    {
        $task->load('users');
        $users = $task->users->whereIn('id', $userIds);
        foreach ($users as $u) {
            $role = Labels::TASK_ROLES[$u->pivot->role] ?? '';
            Notifier::send([$u], 'Sizi tapşırığa əlavə etdilər: '.$task->title,
                auth()->user()->name." sizi \"{$role}\" kimi təyin etdi.".($task->due_date ? ' Deadline: '.Labels::date($task->due_date).'.' : ''),
                route('tasks.show', $task), $task->brand?->name);
        }
    }

    private function canManage(Task $task, User $user): bool
    {
        return $user->isAdmin() || $task->created_by === $user->id
            || ($user->hasPerm('tasks.create') && $user->hasPerm('tasks.all'));
    }

    private function authorizeView(Task $task, User $user): void
    {
        abort_unless($task->canView($user), 403, 'Bu tapşırığa giriş icazəniz yoxdur.');
    }

    public function show(Request $request, Task $task)
    {
        $task->load(['brand', 'users', 'creator', 'comments.user', 'attachments.uploader']);
        $this->authorizeView($task, $request->user());

        return view('tasks.show', [
            'task' => $task,
            'canManage' => $this->canManage($task, $request->user()),
            'staff' => User::staff()->active()->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Task $task)
    {
        abort_unless($this->canManage($task, $request->user()), 403);
        $task->load('users');
        $assigned = ['responsible' => [], 'supporter' => [], 'watcher' => []];
        foreach ($task->users as $u) {
            $assigned[$u->pivot->role][] = $u->id;
        }

        return view('tasks.form', [
            'task' => $task,
            'brands' => Brand::orderBy('name')->get(),
            'staff' => User::staff()->active()->orderBy('name')->get(),
            'assigned' => $assigned,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        abort_unless($this->canManage($task, $request->user()), 403);
        $data = $request->validate($this->rules());
        $task->fill(collect($data)->only(['title', 'description', 'brand_id', 'status', 'priority', 'due_date'])->all());
        if ($task->isDirty('status')) {
            $task->completed_at = $task->status === 'done' ? now() : null;
        }
        $task->save();
        $new = $this->syncPeople($task, $data);
        FileStore::claim($data['attachment_ids'] ?? [], $request->user(), $task, ['brand_id' => $task->brand_id]);
        $this->notifyAssigned($task, $new);
        Activity::log("“{$task->title}” tapşırığını yenilədi", $task);

        return redirect()->route('tasks.show', $task)->with('success', 'Dəyişikliklər yadda saxlanıldı.');
    }

    public function status(Request $request, Task $task)
    {
        $task->load('users');
        $this->authorizeView($task, $request->user());
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Labels::TASK_STATUSES))]]);
        $this->changeStatus($task, $data['status']);

        return back()->with('success', 'Status yeniləndi.');
    }

    private function changeStatus(Task $task, string $status): void
    {
        if ($task->status === $status) {
            return;
        }
        $task->update(['status' => $status, 'completed_at' => $status === 'done' ? now() : null]);
        $label = Labels::label(Labels::TASK_STATUSES, $status);
        Activity::log("“{$task->title}” statusunu “{$label}” etdi", $task);
        Notifier::send($task->users->push($task->creator), "Status dəyişdi: {$task->title}",
            auth()->user()->name." tapşırığın statusunu “{$label}” etdi.", route('tasks.show', $task), $task->brand?->name);
    }

    /** Kanban sürükləmə */
    public function move(Request $request, Task $task)
    {
        $task->load('users');
        $this->authorizeView($task, $request->user());
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Labels::TASK_STATUSES))],
            'order' => ['array'],
            'order.*' => ['integer'],
        ]);
        $this->changeStatus($task, $data['status']);
        $visible = Task::visibleTo($request->user())->whereIn('id', $data['order'] ?? [])->pluck('id')->all();
        foreach ($data['order'] ?? [] as $i => $id) {
            if (in_array((int) $id, $visible, true)) {
                Task::whereKey($id)->update(['position' => $i]);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function comment(Request $request, Task $task)
    {
        $task->load('users', 'creator');
        $this->authorizeView($task, $request->user());
        $data = $request->validate(['body' => ['required', 'string', 'max:10000'], 'attachment_ids' => ['array']]);
        $task->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);
        FileStore::claim($data['attachment_ids'] ?? [], $request->user(), $task, ['brand_id' => $task->brand_id]);

        Notifier::send($task->users->push($task->creator), 'Yeni şərh: '.$task->title,
            $request->user()->name.': '.str($data['body'])->limit(200), route('tasks.show', $task).'#comments', $task->brand?->name);

        return redirect()->to(route('tasks.show', $task).'#comments');
    }

    public function files(Request $request, Task $task)
    {
        $task->load('users');
        $this->authorizeView($task, $request->user());
        $data = $request->validate(['attachment_ids' => ['required', 'array']]);
        $n = FileStore::claim($data['attachment_ids'], $request->user(), $task, ['brand_id' => $task->brand_id]);
        if ($n) {
            Activity::log("“{$task->title}” tapşırığına {$n} fayl yüklədi", $task);
            Notifier::send($task->users->push($task->creator), 'Yeni fayl: '.$task->title,
                $request->user()->name." {$n} fayl əlavə etdi.", route('tasks.show', $task), $task->brand?->name);
        }

        return back()->with('success', 'Fayllar əlavə edildi.');
    }

    /** İstənilən iştirakçı yeni şəxsləri tag edə bilər */
    public function people(Request $request, Task $task)
    {
        $task->load('users');
        $this->authorizeView($task, $request->user());
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', Rule::in(array_keys(Labels::TASK_ROLES))],
        ]);
        $user = User::staff()->findOrFail($data['user_id']);
        $isNew = ! $task->users->contains('id', $user->id);
        $task->users()->syncWithoutDetaching([$user->id => ['role' => $data['role']]]);
        if ($isNew) {
            $this->notifyAssigned($task, [$user->id]);
        }

        return back()->with('success', $user->name.' əlavə edildi.');
    }

    public function destroy(Request $request, Task $task)
    {
        abort_unless($this->canManage($task, $request->user()), 403);
        foreach ($task->attachments as $a) {
            FileStore::delete($a);
        }
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Tapşırıq silindi.');
    }
}
