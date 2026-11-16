@php use App\Support\Labels; @endphp
<x-layouts.app title="İşçilər və icazələr">
  <x-page-header title="İşçilər və icazələr" subtitle="Kim hansı bölmələri görür — buradan idarə edin.">
    <x-slot:actions><a href="{{ route('team.create', ['role' => $tab === 'clients' ? 'client' : 'employee']) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ $tab === 'clients' ? 'Müştəri hesabı aç' : 'İşçi əlavə et' }}</a></x-slot:actions>
  </x-page-header>
  <nav class="mb-4 flex gap-6 border-b border-slate-200">
    <a href="{{ route('team.index') }}" class="tab {{ $tab === 'staff' ? 'active' : '' }}">Agentlik komandası</a>
    <a href="{{ route('team.index', ['tab' => 'clients']) }}" class="tab {{ $tab === 'clients' ? 'active' : '' }}">Müştəri hesabları</a>
  </nav>
  <div class="card overflow-hidden">
    @forelse ($users as $u)
      <a href="{{ route('team.edit', $u) }}" class="flex flex-wrap items-center gap-4 border-b border-slate-50 px-5 py-3 last:border-0 hover:bg-slate-50 {{ $u->is_active ? '' : 'opacity-50' }}">
        <x-avatar :user="$u" size="size-10" />
        <div class="min-w-0 flex-1"><p class="font-medium text-slate-900">{{ $u->name }} @unless($u->is_active)<x-badge color="rose">Deaktiv</x-badge>@endunless</p><p class="truncate text-sm text-slate-500">{{ $u->email }}{{ $u->position ? ' · '.$u->position : '' }}</p></div>
        @if ($tab === 'staff')
          <x-badge :color="$u->isAdmin() ? 'violet' : 'slate'">{{ $u->roleLabel() }}</x-badge>
          <div class="hidden max-w-md flex-wrap gap-1 lg:flex">
            @if ($u->isAdmin())<span class="text-xs text-slate-500">Bütün icazələr</span>
            @else @foreach ($u->permissions ?? [] as $p)<span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-600">{{ Labels::PERMISSIONS[$p][0] ?? $p }}</span>@endforeach @endif
          </div>
          <span class="w-24 text-right text-sm text-slate-500">{{ $u->open_tasks }} açıq iş</span>
        @else
          <span class="text-sm text-slate-600">{{ $u->brands->pluck('name')->implode(', ') ?: 'Marka təyin edilməyib' }}</span>
        @endif
        <span class="hidden w-28 text-right text-xs text-slate-400 sm:block">{{ $u->last_login_at ? 'Son giriş: '.$u->last_login_at->diffForHumans() : 'Hələ daxil olmayıb' }}</span>
      </a>
    @empty
      <x-empty icon="team" title="İstifadəçi yoxdur" />
    @endforelse
  </div>
</x-layouts.app>
