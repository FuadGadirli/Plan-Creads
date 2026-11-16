{{-- 12 aylıq cədvəl. $year, $plans (month => plan), $url (fn month => url), $statusMap, $client --}}
@php use App\Support\Labels; $nowKey = now()->year * 12 + now()->month; @endphp
<div class="mb-4 flex items-center gap-2">
  <a href="{{ request()->fullUrlWithQuery(['year' => $year - 1]) }}" class="btn-secondary px-2"><x-icon name="chev-left" class="size-4" /></a>
  <span class="min-w-16 text-center text-lg font-semibold">{{ $year }}</span>
  <a href="{{ request()->fullUrlWithQuery(['year' => $year + 1]) }}" class="btn-secondary px-2"><x-icon name="chev-right" class="size-4" /></a>
</div>
<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
  @foreach (Labels::MONTHS as $m => $name)
    @php
      $plan = $plans[$m] ?? null; $key = $year * 12 + $m;
      $past = $key < $nowKey; $current = $key === $nowKey; $future = $key > $nowKey;
      $href = $url($m);
    @endphp
    @if ($href)
    <a href="{{ $href }}" class="group relative flex flex-col rounded-xl p-4 transition hover:-translate-y-0.5 hover:shadow-md
        {{ $current ? 'bg-slate-900 text-white shadow-lg ring-2 ring-brand-500' : ($past ? 'bg-white/60 text-slate-400 ring-1 ring-slate-200' : 'bg-white text-slate-800 ring-1 ring-slate-200') }}">
    @else
    <div class="relative flex flex-col rounded-xl bg-slate-50 p-4 text-slate-300 ring-1 ring-dashed ring-slate-200">
    @endif
      <div class="flex items-start justify-between">
        <x-icon name="doc" class="size-8 {{ $current ? 'text-brand-300' : ($past ? 'text-slate-300' : ($plan ? 'text-brand-500' : 'text-slate-300')) }}" />
        @if ($current)<span class="rounded-full bg-brand-500 px-2 py-0.5 text-[10px] font-semibold uppercase">Bu ay</span>@endif
      </div>
      <div class="mt-3 text-lg font-semibold">{{ $name }}</div>
      <div class="text-xs {{ $current ? 'text-slate-300' : '' }}">{{ $year }}</div>
      <div class="mt-3 min-h-6">
        @if ($plan)
          <x-status :map="$statusMap" :value="$plan->status" />
          @php $vc = $plan->versions_count ?? $plan->clientVersions?->count(); @endphp
          @if ($vc > 1)<span class="ml-1 text-[11px] {{ $current ? 'text-slate-300' : 'text-slate-400' }}">v{{ $vc }}</span>@endif
        @else
          <span class="text-xs {{ $current ? 'text-slate-400' : 'text-slate-400' }}">{{ $href ? 'Yüklənməyib' : 'Hələ yoxdur' }}</span>
        @endif
      </div>
    @if ($href)</a>@else</div>@endif
  @endforeach
</div>
