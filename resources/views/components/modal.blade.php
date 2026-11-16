@props(['name', 'title', 'width' => 'max-w-lg'])
<div x-data="{ open: false }" x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true" x-on:keydown.escape.window="open = false" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center">
  <div class="fixed inset-0 bg-slate-900/40" @click="open = false"></div>
  <div class="relative w-full {{ $width }} max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl" x-show="open" x-transition>
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
      <button type="button" @click="open = false" class="rounded p-1 text-slate-400 hover:bg-slate-100"><x-icon name="x" /></button>
    </div>
    {{ $slot }}
  </div>
</div>
