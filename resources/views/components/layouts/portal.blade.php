@php
  $u = auth()->user();
  $agency = \App\Models\Setting::get('agency_name');
  $brands = $portalBrands ?? $u->brands()->where('is_active', true)->orderBy('name')->get();
  $current = $portalBrand ?? $brands->firstWhere('id', session('portal.brand')) ?? $brands->first();
  $nav = [
    ['Ana səhifə', 'portal.dashboard', 'home', 'portal.dashboard'],
    ['Aylıq kontent plan', 'portal.content', 'doc', 'portal.content*'],
    ['Paylaşımlar', 'portal.posts', 'calendar', 'portal.posts*'],
    ['Media portfoliosu', 'portal.media', 'photo', 'portal.media'],
    ['Şirkət ayarları', 'portal.settings', 'settings', 'portal.settings'],
  ];
@endphp
<!DOCTYPE html>
<html lang="az">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ isset($title) ? $title.' · ' : '' }}{{ $current?->name ?? $agency }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased" x-data="{ side: false }">
  <div x-show="side" x-cloak class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="side = false"></div>
  <aside :class="side && 'translate-x-0!'" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 text-slate-300 transition-transform lg:translate-x-0">
    <div class="border-b border-white/10 p-4" x-data="{ open: false }">
      <button @click="open = !open" class="flex w-full items-center gap-3 rounded-lg p-1.5 text-left hover:bg-white/5">
        <x-brand-logo :brand="$current" size="size-10" />
        <div class="min-w-0 flex-1 leading-tight">
          <div class="truncate font-semibold text-white">{{ $current?->name }}</div>
          <div class="text-[11px] text-slate-400">{{ $agency }} ilə əməkdaşlıq</div>
        </div>
        @if ($brands->count() > 1)<x-icon name="chev-down" class="size-4" />@endif
      </button>
      @if ($brands->count() > 1)
        <div x-show="open" x-cloak class="mt-2 space-y-1">
          @foreach ($brands as $b)
            <a href="{{ route('portal.brand', $b) }}" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-white/10 {{ $b->id === $current?->id ? 'text-white' : '' }}"><x-brand-logo :brand="$b" size="size-6" text="text-[10px]" /> {{ $b->name }}</a>
          @endforeach
        </div>
      @endif
    </div>
    <nav class="flex-1 space-y-1 p-3">
      @foreach ($nav as [$label, $route, $icon, $active])
        <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs($active) ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white' }}">
          <x-icon :name="$icon" class="size-5" /> {{ $label }}
        </a>
      @endforeach
    </nav>
    <div class="border-t border-white/10 p-3">
      <div class="flex items-center gap-3 px-2 py-2">
        <x-avatar :user="$u" class="ring-slate-900" />
        <div class="min-w-0 flex-1 leading-tight"><div class="truncate text-sm font-medium text-white">{{ $u->name }}</div><div class="truncate text-xs text-slate-400">{{ $u->email }}</div></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded p-1.5 text-slate-400 hover:bg-white/10 hover:text-white" title="Çıxış"><x-icon name="logout" class="size-4" /></button></form>
      </div>
    </div>
  </aside>
  <div class="lg:pl-64">
    <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
      <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="side = true"><x-icon name="menu" /></button>
      <div class="text-sm text-slate-500">Salam, <span class="font-medium text-slate-800">{{ $u->name }}</span> 👋</div>
      <div class="ml-auto">@include('partials.notifications-bell')</div>
    </header>
    <main class="mx-auto max-w-[1300px] px-4 py-6 sm:px-6 lg:px-8">
      @include('partials.flash')
      {{ $slot }}
    </main>
  </div>
</body>
</html>
