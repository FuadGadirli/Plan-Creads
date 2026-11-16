@props(['brand', 'size' => 'size-10', 'text' => 'text-sm'])
@if ($brand?->logoUrl())
  <img src="{{ $brand->logoUrl() }}" alt="{{ $brand->name }}" {{ $attributes->merge(['class' => "$size shrink-0 rounded-lg object-contain bg-white ring-1 ring-slate-200"]) }}>
@else
  <span style="background: {{ $brand?->color ?? '#6366f1' }}" {{ $attributes->merge(['class' => "$size $text shrink-0 inline-flex items-center justify-center rounded-lg font-bold text-white"]) }}>{{ $brand?->initials() }}</span>
@endif
