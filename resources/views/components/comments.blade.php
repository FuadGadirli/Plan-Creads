@props(['comments', 'action', 'clientToggle' => false, 'placeholder' => 'Şərh yazın…'])
<div id="comments" class="space-y-4">
  @forelse ($comments as $c)
    <div class="flex gap-3">
      <x-avatar :user="$c->user" />
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 text-sm">
          <span class="font-medium text-slate-900">{{ $c->user?->name ?? 'Silinmiş istifadəçi' }}</span>
          @if ($c->user?->isClient())<x-badge color="violet">Müştəri</x-badge>@endif
          @if ($c->visibility === 'client' && ! $c->user?->isClient())<x-badge color="blue">Müştəri görür</x-badge>@endif
          <span class="text-xs text-slate-400">{{ \App\Support\Labels::date($c->created_at, true) }}</span>
        </div>
        <div class="prose-plain mt-1 rounded-lg rounded-tl-none {{ $c->user?->isClient() ? 'bg-violet-50' : 'bg-slate-50' }} px-3 py-2">{{ $c->body }}</div>
      </div>
    </div>
  @empty
    <p class="text-sm text-slate-500">Hələ şərh yoxdur.</p>
  @endforelse

  <form method="POST" action="{{ $action }}" class="flex gap-3 pt-2">
    @csrf
    <x-avatar :user="auth()->user()" />
    <div class="flex-1 space-y-2">
      <textarea name="body" rows="2" required class="input" placeholder="{{ $placeholder }}"></textarea>
      {{ $slot }}
      <div class="flex items-center justify-between gap-2">
        @if ($clientToggle)
          <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="to_client" value="1" class="rounded border-slate-300 text-brand-600"> Müştəri də görsün</label>
        @else <span></span> @endif
        <button class="btn-primary btn-sm"><x-icon name="send" class="size-4" /> Göndər</button>
      </div>
    </div>
  </form>
</div>
