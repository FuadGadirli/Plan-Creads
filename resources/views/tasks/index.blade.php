@php use App\Support\Labels; $u = auth()->user(); $q = request()->except('view'); @endphp
<x-layouts.app :title="$title">
  @if ($brand)
    @include('brands._header', ['brand' => $brand, 'section' => 'tasks'])
  @else
  <x-page-header :title="$title" :subtitle="$mine ? 'Bütün markalarda sizə aid olan tapşırıqlar bir yerdə.' : 'Agentliyin bütün tapşırıqları.'">
    <x-slot:actions>
      <div class="seg">
        <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" class="{{ $view === 'list' ? 'on' : '' }}"><x-icon name="list" class="inline size-4" /> Siyahı</a>
        <a href="{{ request()->fullUrlWithQuery(['view' => 'kanban']) }}" class="{{ $view === 'kanban' ? 'on' : '' }}"><x-icon name="kanban" class="inline size-4" /> Kanban</a>
      </div>
      @if ($u->hasPerm('tasks.create'))<a href="{{ route('tasks.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Tapşırıq ver</a>@endif
    </x-slot:actions>
  </x-page-header>
  @endif

  @if ($brand)
    <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
      <div class="seg">
        <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" class="{{ $view === 'list' ? 'on' : '' }}">Siyahı</a>
        <a href="{{ request()->fullUrlWithQuery(['view' => 'kanban']) }}" class="{{ $view === 'kanban' ? 'on' : '' }}">Kanban</a>
      </div>
      @if ($u->hasPerm('tasks.create'))<a href="{{ route('tasks.create', ['brand' => $brand->id]) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Tapşırıq ver</a>@endif
    </div>
  @endif

  {{-- Filtrlər --}}
  <form class="card mb-4 flex flex-wrap items-end gap-3 p-3">
    <input type="hidden" name="view" value="{{ $view }}">
    <div class="min-w-[180px] flex-1"><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Başlığa görə axtar…" class="input"></div>
    @unless ($brand)
      <select name="brand" class="input w-auto" onchange="this.form.submit()">
        <option value="">Bütün markalar</option>
        @foreach ($brands as $b)<option value="{{ $b->id }}" @selected(($filters['brand'] ?? '') == $b->id)>{{ $b->name }}</option>@endforeach
      </select>
    @endunless
    @unless ($mine)
      <select name="user" class="input w-auto" onchange="this.form.submit()">
        <option value="">Bütün işçilər</option>
        @foreach ($staff as $s)<option value="{{ $s->id }}" @selected(($filters['user'] ?? '') == $s->id)>{{ $s->name }}</option>@endforeach
      </select>
    @endunless
    @if ($view === 'list')
      <select name="status" class="input w-auto" onchange="this.form.submit()">
        <option value="">Açıq tapşırıqlar</option>
        @foreach (Labels::TASK_STATUSES as $k => [$l]) <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option> @endforeach
      </select>
    @endif
    <select name="priority" class="input w-auto" onchange="this.form.submit()">
      <option value="">İstənilən prioritet</option>
      @foreach (Labels::PRIORITIES as $k => [$l]) <option value="{{ $k }}" @selected(($filters['priority'] ?? '') === $k)>{{ $l }}</option> @endforeach
    </select>
    <button class="btn-secondary"><x-icon name="search" class="size-4" /> Axtar</button>
    @if (array_filter($filters))<a href="{{ url()->current() }}?view={{ $view }}" class="btn-ghost">Təmizlə</a>@endif
  </form>

  @if ($view === 'kanban')
    <div x-data="kanban('{{ route('tasks.move', '__ID__') }}')" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
      @foreach (Labels::TASK_STATUSES as $key => [$label, $color])
        @php $col = $tasks->where('status', $key); $dot = ['slate' => 'bg-slate-400', 'blue' => 'bg-blue-500', 'amber' => 'bg-amber-500', 'emerald' => 'bg-emerald-500'][$color]; @endphp
        <div class="flex flex-col rounded-xl bg-slate-100/70 p-3">
          <div class="mb-3 flex items-center gap-2 px-1">
            <span class="size-2.5 rounded-full {{ $dot }}"></span>
            <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
            <span class="ml-auto rounded-full bg-white px-2 text-xs font-medium text-slate-500" data-count="{{ $key }}">{{ $col->count() }}</span>
          </div>
          <div data-column="{{ $key }}" class="min-h-[120px] flex-1 space-y-2">
            @foreach ($col as $t)
              @include('tasks._card', ['t' => $t])
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div class="card overflow-hidden">
      <div class="hidden grid-cols-12 gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-slate-500 md:grid">
        <div class="col-span-5">Tapşırıq</div><div class="col-span-2">Məsul şəxslər</div><div class="col-span-2">Status</div><div class="col-span-1">Verilib</div><div class="col-span-2 text-right">Deadline</div>
      </div>
      @forelse ($tasks as $t)
        <a href="{{ route('tasks.show', $t) }}" class="grid grid-cols-1 gap-2 border-b border-slate-50 px-5 py-3 last:border-0 hover:bg-slate-50 md:grid-cols-12 md:items-center md:gap-3">
          <div class="col-span-5 flex min-w-0 items-center gap-3">
            <x-brand-logo :brand="$t->brand" size="size-9" text="text-[10px]" />
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-slate-900">{{ $t->title }}</p>
              <p class="flex items-center gap-2 text-xs text-slate-500">
                <span>{{ $t->brand?->name ?? 'Ümumi' }}</span>
                @if (in_array($t->priority, ['high', 'urgent']))<x-status :map="Labels::PRIORITIES" :value="$t->priority" />@endif
                @if ($t->comments_count)<span class="inline-flex items-center gap-0.5"><x-icon name="chat" class="size-3.5" />{{ $t->comments_count }}</span>@endif
                @if ($t->attachments_count)<span class="inline-flex items-center gap-0.5"><x-icon name="clip" class="size-3.5" />{{ $t->attachments_count }}</span>@endif
              </p>
            </div>
          </div>
          <div class="col-span-2 flex -space-x-2">
            @foreach ($t->users->sortBy(fn ($x) => $x->pivot->role !== 'responsible')->take(4) as $p)<x-avatar :user="$p" size="size-7" />@endforeach
            @if ($t->users->count() > 4)<span class="inline-flex size-7 items-center justify-center rounded-full bg-slate-200 text-[10px] font-semibold ring-2 ring-white">+{{ $t->users->count() - 4 }}</span>@endif
          </div>
          <div class="col-span-2"><x-status :map="Labels::TASK_STATUSES" :value="$t->status" /></div>
          <div class="col-span-1 text-xs text-slate-500">{{ Labels::shortDate($t->created_at) }}</div>
          <div class="col-span-2 text-right text-sm {{ $t->isOverdue() ? 'font-semibold text-rose-600' : 'text-slate-600' }}">
            @if ($t->isOverdue())<x-icon name="alert" class="inline size-4" />@endif
            {{ $t->due_date ? Labels::date($t->due_date) : '—' }}
          </div>
        </a>
      @empty
        <x-empty icon="tasks" title="Tapşırıq tapılmadı" text="Filtrləri dəyişin və ya yeni tapşırıq yaradın." />
      @endforelse
    </div>
  @endif
</x-layouts.app>
