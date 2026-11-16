<x-page-header title="Bildirişlər">
  <x-slot:actions><form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn-secondary">Hamısını oxunmuş et</button></form></x-slot:actions>
</x-page-header>
<div class="card divide-y divide-slate-100">
  @forelse ($notifications as $n)
    <a href="{{ route('notifications.open', $n->id) }}" class="flex gap-3 px-5 py-3 hover:bg-slate-50 {{ $n->read_at ? '' : 'bg-brand-50/40' }}">
      <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $n->read_at ? 'bg-transparent' : 'bg-brand-500' }}"></span>
      <div class="min-w-0 flex-1">
        @if ($n->data['brand'] ?? null)<p class="text-xs font-medium text-brand-600">{{ $n->data['brand'] }}</p>@endif
        <p class="text-sm font-medium text-slate-900">{{ $n->data['title'] ?? '' }}</p>
        <p class="prose-plain text-slate-600">{{ $n->data['body'] ?? '' }}</p>
      </div>
      <span class="shrink-0 text-xs text-slate-400">{{ $n->created_at->diffForHumans() }}</span>
    </a>
  @empty
    <x-empty icon="bell" title="Bildiriş yoxdur" />
  @endforelse
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
