@props(['user', 'size' => 'size-8'])
@php
  $colors = ['bg-indigo-500','bg-sky-500','bg-emerald-500','bg-amber-500','bg-rose-500','bg-violet-500','bg-teal-500','bg-fuchsia-500'];
  $c = $colors[($user?->id ?? 0) % count($colors)];
@endphp
@if ($user?->avatarUrl())
  <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" title="{{ $user->name }}" {{ $attributes->merge(['class' => "$size rounded-full object-cover ring-2 ring-white"]) }}>
@else
  <span title="{{ $user?->name }}" {{ $attributes->merge(['class' => "$size $c inline-flex shrink-0 items-center justify-center rounded-full text-[0.7rem] font-semibold text-white ring-2 ring-white"]) }}>{{ $user?->initials() ?? '?' }}</span>
@endif
