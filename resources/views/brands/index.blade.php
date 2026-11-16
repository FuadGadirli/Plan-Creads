<x-layouts.app title="Markalar">
  <x-page-header title="Markalar" :subtitle="$showInactive ? 'Deaktiv markalar' : 'Hər marka bir qovluqdur: tapşırıqlar, fayllar, sosial media plan və kontent plan içindədir.'">
    <x-slot:actions>
      <a href="{{ route('brands.index', $showInactive ? [] : ['inactive' => 1]) }}" class="btn-ghost">{{ $showInactive ? 'Aktiv markalar' : 'Deaktiv markalar' }}</a>
      @if (auth()->user()->hasPerm('brands.manage'))<a href="{{ route('brands.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Yeni marka</a>@endif
    </x-slot:actions>
  </x-page-header>

  @if ($brands->isEmpty())
    <div class="card"><x-empty icon="brands" title="Hələ marka yoxdur" text="İlk markanı yaradın: loqo, rəng və müştəri məlumatı əlavə edin." /></div>
  @else
  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
    @foreach ($brands as $b)
      <a href="{{ route('brands.show', $b) }}" class="group card relative overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="h-2" style="background: {{ $b->color }}"></div>
        <div class="p-5">
          <div class="flex items-start gap-4">
            <x-brand-logo :brand="$b" size="size-14" text="text-lg" />
            <div class="min-w-0 flex-1">
              <h3 class="truncate text-lg font-semibold text-slate-900 group-hover:text-brand-700">{{ $b->name }}</h3>
              <p class="truncate text-sm text-slate-500">{{ $b->client?->name ?? 'Marka' }}</p>
            </div>
          </div>
          <div class="mt-5 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-lg bg-slate-50 py-2"><div class="text-lg font-semibold text-slate-900">{{ $b->open_tasks }}</div><div class="text-[11px] text-slate-500">Aktiv tapşırıq</div></div>
            <div class="rounded-lg bg-slate-50 py-2"><div class="text-lg font-semibold text-slate-900">{{ $b->month_posts }}</div><div class="text-[11px] text-slate-500">Bu ay post</div></div>
            <div class="rounded-lg {{ $b->awaiting_posts ? 'bg-amber-50' : 'bg-slate-50' }} py-2"><div class="text-lg font-semibold {{ $b->awaiting_posts ? 'text-amber-700' : 'text-slate-900' }}">{{ $b->awaiting_posts }}</div><div class="text-[11px] text-slate-500">Təsdiqdə</div></div>
          </div>
          @if ($b->overdue_tasks)
            <p class="mt-3 flex items-center gap-1 text-xs font-medium text-rose-600"><x-icon name="alert" class="size-3.5" /> {{ $b->overdue_tasks }} gecikən tapşırıq</p>
          @endif
        </div>
      </a>
    @endforeach
  </div>
  @endif
</x-layouts.app>
