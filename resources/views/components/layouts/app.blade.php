@php
  $u = auth()->user();
  $agency = \App\Models\Setting::get('agency_name');
  $pendingApprovals = $u->hasPerm('approve') ? \App\Models\Post::where('status', 'pending')->count() + \App\Models\ContentPlanVersion::where('status', 'pending')->count() : 0;
  $nav = [
    ['Ana səhifə', 'dashboard', 'home', true, 'dashboard'],
    ['Mənim işlərim', 'tasks.mine', 'inbox', true, 'tasks.mine'],
    ['Tapşırıqlar', 'tasks.index', 'tasks', true, 'tasks.index|tasks.create|tasks.show|tasks.edit'],
    ['Təsdiq gözləyənlər', 'approvals.index', 'approve', $u->hasPerm('approve'), 'approvals.*'],
    ['Markalar', 'brands.index', 'brands', $u->hasPerm('brands'), 'brands.*|posts.*|content.*'],
    ['Əməkdaşlıq', 'clients.index', 'clients', $u->hasPerm('crm'), 'clients.*'],
    ['İşçilər və icazələr', 'team.index', 'team', $u->hasPerm('team'), 'team.*'],
    ['Ayarlar', 'settings.index', 'settings', true, 'settings.*'],
  ];
@endphp
<!DOCTYPE html>
<html lang="az">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ isset($title) ? $title.' · ' : '' }}{{ $agency }} Plan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased" x-data="{ side: false }">
  {{-- Sol menyu --}}
  <div x-show="side" x-cloak class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="side = false"></div>
  <aside :class="side && 'translate-x-0!'" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
    <div class="flex h-16 items-center gap-2.5 border-b border-slate-100 px-5">
      <span class="inline-flex size-8 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">{{ mb_substr($agency, 0, 1) }}</span>
      <div class="leading-tight"><div class="font-semibold text-slate-900">{{ $agency }} Plan</div><div class="text-[11px] text-slate-500">Agentlik paneli</div></div>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
      @foreach ($nav as [$label, $route, $icon, $show, $active])
        @if ($show)
          <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs(...explode('|', $active)) ? 'active' : '' }}">
            <x-icon :name="$icon" class="size-5 shrink-0" /> <span class="flex-1">{{ $label }}</span>
            @if ($route === 'approvals.index' && $pendingApprovals)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ $pendingApprovals }}</span>@endif
          </a>
        @endif
      @endforeach
    </nav>
    <div class="border-t border-slate-100 p-3">
      <div class="flex items-center gap-3 rounded-lg px-2 py-2">
        <x-avatar :user="$u" />
        <div class="min-w-0 flex-1 leading-tight">
          <div class="truncate text-sm font-medium text-slate-900">{{ $u->name }}</div>
          <div class="truncate text-xs text-slate-500">{{ $u->position ?: $u->roleLabel() }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Çıxış"><x-icon name="logout" class="size-4" /></button></form>
      </div>
    </div>
  </aside>

  <div class="lg:pl-64">
    <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
      <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="side = true"><x-icon name="menu" /></button>
      <form action="{{ route('search') }}" class="relative max-w-md flex-1">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
        <input name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Axtar: tapşırıq, marka, “Marka noyabr”, fayl…" class="input bg-slate-50 pl-9">
      </form>
      <div class="ml-auto flex items-center gap-1">
        @if ($u->hasPerm('tasks.create'))
          <a href="{{ route('tasks.create') }}" class="btn-primary hidden sm:inline-flex"><x-icon name="plus" class="size-4" /> Tapşırıq ver</a>
        @endif
        @include('partials.notifications-bell')
      </div>
    </header>

    <main class="mx-auto max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8">
      @include('partials.flash')
      {{ $slot }}
    </main>
  </div>
</body>
</html>
