@php use App\Support\Labels; @endphp
<x-layouts.app :title="$task->title">
  <x-page-header :title="$task->title">
    <x-slot:breadcrumb>
      <a href="{{ route('tasks.index') }}" class="hover:underline">Tapşırıqlar</a>
      @if ($task->brand) / <a href="{{ route('brands.tasks', $task->brand) }}" class="hover:underline">{{ $task->brand->name }}</a>@endif
    </x-slot:breadcrumb>
    <x-slot:actions>
      @if ($canManage)
        <a href="{{ route('tasks.edit', $task) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Redaktə</a>
        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Tapşırıq silinsin? Bu əməliyyat geri qaytarılmır.')">@csrf @method('DELETE')<button class="btn-danger"><x-icon name="trash" class="size-4" /></button></form>
      @endif
    </x-slot:actions>
  </x-page-header>

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
      {{-- Mərhələ seçimi --}}
      <form method="POST" action="{{ route('tasks.status', $task) }}" class="card flex flex-wrap items-center gap-2 p-3">
        @csrf @method('PATCH')
        <span class="px-2 text-sm font-medium text-slate-600">Mərhələ:</span>
        @foreach (Labels::TASK_STATUSES as $k => [$l, $c])
          @php $on = $task->status === $k; $styles = ['slate' => 'bg-slate-700', 'blue' => 'bg-blue-600', 'amber' => 'bg-amber-500', 'emerald' => 'bg-emerald-600']; @endphp
          <button name="status" value="{{ $k }}" class="btn btn-sm {{ $on ? $styles[$c].' text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">@if($on)<x-icon name="check" class="size-3.5" />@endif {{ $l }}</button>
        @endforeach
      </form>

      <div class="card p-6">
        <h2 class="mb-3 font-semibold text-slate-900">Təsvir</h2>
        @if ($task->description)<div class="prose-plain">{{ $task->description }}</div>
        @else<p class="text-sm text-slate-400">Təsvir əlavə edilməyib.</p>@endif
      </div>

      <div class="card p-6">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-semibold text-slate-900">Fayllar <span class="text-slate-400">({{ $task->attachments->count() }})</span></h2>
        </div>
        @if ($task->attachments->isNotEmpty())
          <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($task->attachments as $f)
              <x-file-tile :file="$f">
                @if (auth()->user()->isAdmin() || $f->uploaded_by === auth()->id())
                  <form method="POST" action="{{ route('files.destroy', $f) }}" onsubmit="return confirm('Fayl silinsin?')">@csrf @method('DELETE')<button class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button></form>
                @endif
              </x-file-tile>
            @endforeach
          </div>
        @endif
        <form method="POST" action="{{ route('tasks.files', $task) }}">@csrf
          <x-uploader scope="tasks" :brand="$task->brand" :autosubmit="true" compact />
        </form>
      </div>

      <div class="card p-6">
        <h2 class="mb-4 font-semibold text-slate-900">Şərhlər</h2>
        <x-comments :comments="$task->comments" :action="route('tasks.comment', $task)" />
      </div>
    </div>

    <div class="space-y-6">
      <div class="card divide-y divide-slate-100">
        <dl class="space-y-3 p-5 text-sm">
          <div class="flex justify-between gap-3"><dt class="text-slate-500">Status</dt><dd><x-status :map="Labels::TASK_STATUSES" :value="$task->status" /></dd></div>
          <div class="flex justify-between gap-3"><dt class="text-slate-500">Prioritet</dt><dd><x-status :map="Labels::PRIORITIES" :value="$task->priority" /></dd></div>
          <div class="flex justify-between gap-3"><dt class="text-slate-500">Marka</dt><dd class="font-medium">@if($task->brand)<a href="{{ route('brands.show', $task->brand) }}" class="hover:underline">{{ $task->brand->name }}</a>@else Ümumi @endif</dd></div>
          <div class="flex justify-between gap-3"><dt class="text-slate-500">Deadline</dt><dd class="font-medium {{ $task->isOverdue() ? 'text-rose-600' : '' }}">{{ Labels::date($task->due_date) }}</dd></div>
          <div class="flex justify-between gap-3"><dt class="text-slate-500">Verilmə tarixi</dt><dd>{{ Labels::date($task->created_at, true) }}</dd></div>
          <div class="flex justify-between gap-3"><dt class="text-slate-500">Verən</dt><dd>{{ $task->creator?->name ?? '—' }}</dd></div>
          @if ($task->completed_at)<div class="flex justify-between gap-3"><dt class="text-slate-500">Bitib</dt><dd>{{ Labels::date($task->completed_at, true) }}</dd></div>@endif
        </dl>
        @foreach (Labels::TASK_ROLES as $role => $label)
          @php $people = $task->users->where('pivot.role', $role); @endphp
          @if ($people->isNotEmpty())
            <div class="p-5">
              <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
              <ul class="space-y-2">@foreach ($people as $p)<li class="flex items-center gap-2 text-sm"><x-avatar :user="$p" size="size-7" /> {{ $p->name }}</li>@endforeach</ul>
            </div>
          @endif
        @endforeach
        <form method="POST" action="{{ route('tasks.people', $task) }}" class="space-y-2 p-5">
          @csrf
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Şəxs tag et</p>
          <select name="user_id" class="input" required>
            <option value="">İşçi seçin…</option>
            @foreach ($staff->whereNotIn('id', $task->users->pluck('id')) as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
          </select>
          <div class="flex gap-2">
            <select name="role" class="input">@foreach (Labels::TASK_ROLES as $k => $l)<option value="{{ $k }}" @selected($k === 'watcher')>{{ $l }}</option>@endforeach</select>
            <button class="btn-secondary"><x-icon name="plus" class="size-4" /></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-layouts.app>
