@props(['color' => 'slate'])
@php
$map = [
  'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
  'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
  'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
  'orange' => 'bg-orange-50 text-orange-700 ring-orange-200',
  'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
  'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
  'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset whitespace-nowrap '.($map[$color] ?? $map['slate'])]) }}>{{ $slot }}</span>
