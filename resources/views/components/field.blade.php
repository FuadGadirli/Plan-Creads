@props(['label' => null, 'name' => null, 'hint' => null])
<div {{ $attributes }}>
  @if ($label)<label class="label" @if($name) for="{{ $name }}" @endif>{{ $label }}</label>@endif
  {{ $slot }}
  @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
  @if ($name) @error($name)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror @endif
</div>
