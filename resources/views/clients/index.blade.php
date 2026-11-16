@php use App\Support\Labels; @endphp
<x-layouts.app title="Əməkdaşlıq">
  <x-page-header title="Əməkdaşlıq" subtitle="Mövcud və potensial müştərilər, əlaqə məlumatları, görüşlər və sənədlər.">
    <x-slot:actions><a href="{{ route('clients.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Yeni müştəri</a></x-slot:actions>
  </x-page-header>
  <div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('clients.index') }}" class="btn btn-sm {{ ! $status ? 'bg-slate-900 text-white' : 'bg-white ring-1 ring-slate-200' }}">Hamısı ({{ $counts->sum() }})</a>
    @foreach (Labels::CLIENT_STATUSES_CRM as $k => [$l])
      <a href="{{ route('clients.index', ['status' => $k]) }}" class="btn btn-sm {{ $status === $k ? 'bg-slate-900 text-white' : 'bg-white ring-1 ring-slate-200' }}">{{ $l }} ({{ $counts[$k] ?? 0 }})</a>
    @endforeach
    <form class="ml-auto"><input type="hidden" name="status" value="{{ $status }}"><input name="q" value="{{ request('q') }}" class="input py-1.5" placeholder="Ad və ya əlaqə şəxsi…"></form>
  </div>
  <div class="card overflow-hidden">
    @forelse ($clients as $c)
      <a href="{{ route('clients.show', $c) }}" class="grid grid-cols-1 items-center gap-2 border-b border-slate-50 px-5 py-3 last:border-0 hover:bg-slate-50 md:grid-cols-12">
        <div class="md:col-span-4"><p class="font-medium text-slate-900">{{ $c->name }}</p><p class="text-xs text-slate-500">{{ $c->industry ?: '—' }}</p></div>
        <div class="text-sm text-slate-600 md:col-span-3">{{ $c->contact_person ?: '—' }}<div class="text-xs text-slate-400">{{ $c->email ?: $c->phone }}</div></div>
        <div class="md:col-span-2"><x-status :map="Labels::CLIENT_STATUSES_CRM" :value="$c->status" /></div>
        <div class="text-xs text-slate-500 md:col-span-3 md:text-right">Son toxunuş: {{ $c->events_max_happened_at ? Labels::date($c->events_max_happened_at) : '—' }}<br>{{ $c->brands->pluck('name')->implode(', ') }}</div>
      </a>
    @empty
      <x-empty icon="clients" title="Müştəri yoxdur" text="“Yeni müştəri” ilə ilk kartı yaradın." />
    @endforelse
  </div>
</x-layouts.app>
