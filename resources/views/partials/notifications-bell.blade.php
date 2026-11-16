<div x-data="notifications('{{ route('notifications.count') }}')" class="relative">
  <button @click="open = !open" class="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Bildirişlər">
    <x-icon name="bell" />
    <span x-show="count" x-cloak x-text="count > 9 ? '9+' : count" class="absolute -right-0.5 -top-0.5 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"></span>
  </button>
  <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute right-0 z-40 mt-2 w-80 overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
      <span class="text-sm font-semibold">Bildirişlər</span>
      <form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="text-xs text-brand-600 hover:underline">Hamısını oxu</button></form>
    </div>
    <template x-if="!items.length"><p class="px-4 py-6 text-center text-sm text-slate-500">Yeni bildiriş yoxdur</p></template>
    <template x-for="n in items" :key="n.id">
      <a :href="n.url" class="block border-b border-slate-50 px-4 py-3 hover:bg-slate-50">
        <p class="text-xs font-medium text-brand-600" x-show="n.brand" x-text="n.brand"></p>
        <p class="text-sm font-medium text-slate-800" x-text="n.title"></p>
        <p class="line-clamp-2 text-xs text-slate-500" x-text="n.body"></p>
        <p class="mt-1 text-[11px] text-slate-400" x-text="n.time"></p>
      </a>
    </template>
    <a href="{{ route('notifications.index') }}" class="block px-4 py-2.5 text-center text-sm font-medium text-brand-600 hover:bg-slate-50">Bütün bildirişlər</a>
  </div>
</div>
