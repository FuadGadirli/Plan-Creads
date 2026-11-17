@props(['label', 'value', 'icon' => 'chart', 'tone' => 'brand', 'href' => null, 'hint' => null])
@php
  $tones = ['brand' => 'bg-brand-50 text-brand-600', 'rose' => 'bg-rose-50 text-rose-600', 'amber' => 'bg-amber-50 text-amber-600', 'emerald' => 'bg-emerald-50 text-emerald-600', 'blue' => 'bg-blue-50 text-blue-600', 'violet' => 'bg-violet-50 text-violet-600'];
  $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="card flex items-center gap-4 p-4 {{ $href ? 'hover:ring-brand-200 transition' : '' }}">
  <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['brand'] }}"><x-icon :name="$icon" /></span>
  <div class="min-w-0">
    <div class="text-2xl font-semibold tracking-tight text-slate-900">{{ $value }}</div>
    <div class="truncate text-sm text-slate-500">{{ $label }}</div>
    @if ($hint)<div class="text-xs text-slate-400">{{ $hint }}</div>@endif
  </div>
</{{ $tag }}>
