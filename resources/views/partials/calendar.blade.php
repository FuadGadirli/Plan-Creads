{{--
  Paylaşım təqvimi. Parametrlər:
  $month (Carbon), $byDate (date => posts), $view ('calendar'|'list'),
  $postUrl (fn Post => url), $addUrl (fn date => url|null), $client (bool)
--}}
@php
  use App\Support\Labels; use App\Support\Calendar;
  $client = $client ?? false;
  $statusMap = $client ? Labels::CLIENT_STATUSES : Labels::APPROVAL_STATUSES;
  $prev = $month->copy()->subMonth()->format('Y-m'); $next = $month->copy()->addMonth()->format('Y-m');
  $dots = ['slate' => 'bg-slate-400', 'amber' => 'bg-amber-500', 'orange' => 'bg-orange-500', 'blue' => 'bg-blue-500', 'rose' => 'bg-rose-500', 'emerald' => 'bg-emerald-500', 'violet' => 'bg-violet-500'];
@endphp
<div class="mb-4 flex flex-wrap items-center gap-3">
  <div class="flex items-center gap-1">
    <a href="{{ request()->fullUrlWithQuery(['month' => $prev]) }}" class="btn-secondary px-2"><x-icon name="chev-left" class="size-4" /></a>
    <a href="{{ request()->fullUrlWithQuery(['month' => now()->format('Y-m')]) }}" class="btn-secondary">Bu ay</a>
    <a href="{{ request()->fullUrlWithQuery(['month' => $next]) }}" class="btn-secondary px-2"><x-icon name="chev-right" class="size-4" /></a>
  </div>
  <h2 class="text-lg font-semibold text-slate-900">{{ Labels::monthYear($month->year, $month->month) }}</h2>
  <div class="ml-auto flex flex-wrap items-center gap-3">
    <div class="hidden items-center gap-3 text-xs text-slate-500 md:flex">
      @foreach (Labels::POST_TYPES as [$l, $c])<span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-sm" style="background: {{ $c }}"></span>{{ $l }}</span>@endforeach
    </div>
    <div class="seg">
      <a href="{{ request()->fullUrlWithQuery(['view' => 'calendar']) }}" class="{{ $view === 'calendar' ? 'on' : '' }}"><x-icon name="calendar" class="inline size-4" /> Təqvim</a>
      <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" class="{{ $view === 'list' ? 'on' : '' }}"><x-icon name="list" class="inline size-4" /> Siyahı</a>
    </div>
  </div>
</div>

@if ($view === 'calendar')
  <div class="card overflow-hidden">
    <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
      @foreach (Labels::WEEKDAYS_SHORT as $d)<div class="py-2">{{ $d }}</div>@endforeach
    </div>
    <div class="grid grid-cols-7">
      @foreach (Calendar::weeks($month) as $week)
        @foreach ($week as $day)
          @php $posts = $byDate[$day->toDateString()] ?? collect(); $in = $day->month === $month->month; $add = $addUrl ? $addUrl($day) : null; @endphp
          <div class="group relative min-h-[110px] border-b border-r border-slate-100 p-1.5 sm:min-h-[130px] {{ $in ? 'bg-white' : 'bg-slate-50/70' }}">
            <div class="mb-1 flex items-center justify-between">
              <span class="inline-flex size-6 items-center justify-center rounded-full text-xs font-medium {{ $day->isToday() ? 'bg-brand-600 text-white' : ($in ? 'text-slate-700' : 'text-slate-400') }}">{{ $day->day }}</span>
              @if ($add && $in)<a href="{{ $add }}" class="rounded p-0.5 text-slate-300 opacity-0 hover:bg-brand-50 hover:text-brand-600 group-hover:opacity-100" title="Paylaşım əlavə et"><x-icon name="plus" class="size-4" /></a>@endif
            </div>
            <div class="space-y-1">
              @foreach ($posts as $p)
                <a href="{{ $postUrl($p) }}" class="block truncate rounded-md px-1.5 py-1 text-[11px] font-medium leading-tight text-white shadow-sm hover:opacity-90 sm:text-xs" style="background: {{ $p->typeColor() }}" title="{{ $p->title }}">
                  <span class="mr-0.5 inline-block size-1.5 rounded-full bg-white/90 align-middle {{ in_array($p->status, ['client_approved', 'published']) ? '' : 'opacity-0' }}"></span><span class="hidden sm:inline">{{ $p->publish_time ? $p->publish_time.' ' : '' }}</span>{{ $p->title }}
                </a>
              @endforeach
            </div>
          </div>
        @endforeach
      @endforeach
    </div>
  </div>
  <p class="mt-2 text-xs text-slate-500">Ağ nöqtə — müştəri təsdiqləyib. Paylaşımın üzərinə basaraq detallara baxın.</p>
@else
  <div class="card divide-y divide-slate-100">
    @foreach (Calendar::days($month) as $day)
      @php $posts = $byDate[$day->toDateString()] ?? collect(); $add = $addUrl ? $addUrl($day) : null; @endphp
      <div class="flex gap-4 px-4 py-3 {{ $day->isToday() ? 'bg-brand-50/50' : '' }} {{ $posts->isEmpty() ? 'opacity-70' : '' }}">
        <div class="w-16 shrink-0 text-center">
          <div class="text-xl font-semibold leading-none {{ $day->isToday() ? 'text-brand-700' : 'text-slate-900' }}">{{ $day->day }}</div>
          <div class="text-xs text-slate-500">{{ Labels::WEEKDAYS_SHORT[$day->dayOfWeekIso - 1] }}</div>
        </div>
        <div class="min-w-0 flex-1 space-y-2">
          @forelse ($posts as $p)
            <a href="{{ $postUrl($p) }}" class="flex items-center gap-3 rounded-lg border-l-4 bg-slate-50 px-3 py-2 hover:bg-slate-100" style="border-color: {{ $p->typeColor() }}">
              @php $img = $p->attachments->first(fn ($a) => in_array($a->kind(), ['image', 'video'])); @endphp
              @if ($img && $img->thumbUrl())<img src="{{ $img->thumbUrl() }}" class="size-10 shrink-0 rounded object-cover" alt="" loading="lazy" onerror="this.remove()">@endif
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900">{{ $p->title }}</p>
                <p class="truncate text-xs text-slate-500">{{ $p->typeLabel() }}{{ $p->publish_time ? ' · '.$p->publish_time : '' }}{{ $p->platforms ? ' · '.collect($p->platforms)->map(fn ($x) => Labels::PLATFORMS[$x] ?? $x)->implode(', ') : '' }}</p>
              </div>
              <x-status :map="$statusMap" :value="$p->status" />
            </a>
          @empty
            <div class="flex h-full items-center text-sm text-slate-400">
              @if ($add)<a href="{{ $add }}" class="inline-flex items-center gap-1 hover:text-brand-600"><x-icon name="plus" class="size-4" /> Paylaşım əlavə et</a>@else — @endif
            </div>
          @endforelse
        </div>
      </div>
    @endforeach
  </div>
@endif
