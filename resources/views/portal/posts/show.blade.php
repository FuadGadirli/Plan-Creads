@php use App\Support\Labels; @endphp
<x-layouts.portal :title="$post->title">
  <div class="mb-4"><a href="{{ route('portal.posts', ['month' => $post->publish_date->format('Y-m')]) }}" class="text-sm text-slate-500 hover:underline">← Təqvimə qayıt</a></div>
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
    <div class="space-y-4 lg:col-span-3">
      @forelse ($post->attachments as $f)
        <div class="card overflow-hidden">@include('files._embed', ['file' => $f, 'height' => 'h-[520px]'])</div>
      @empty
        <div class="card"><x-empty icon="photo" title="Vizual əlavə edilməyib" /></div>
      @endforelse
    </div>
    <div class="space-y-6 lg:col-span-2">
      <div class="card p-6">
        <div class="mb-3 flex flex-wrap items-center gap-2">
          <span class="rounded px-2 py-0.5 text-xs font-semibold text-white" style="background: {{ $post->typeColor() }}">{{ $post->typeLabel() }}</span>
          <x-status :map="Labels::CLIENT_STATUSES" :value="$post->status" />
        </div>
        <h1 class="text-xl font-semibold text-slate-900">{{ $post->title }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ Labels::date($post->publish_date) }}{{ $post->publish_time ? ', '.$post->publish_time : '' }}{{ $post->platforms ? ' · '.collect($post->platforms)->map(fn ($x) => Labels::PLATFORMS[$x] ?? $x)->implode(', ') : '' }}</p>
        <h3 class="mb-1 mt-5 text-sm font-medium text-slate-500">Paylaşım mətni</h3>
        <div class="prose-plain rounded-lg bg-slate-50 p-4">{{ $post->caption ?: '—' }}</div>
        @if ($post->notes)<h3 class="mb-1 mt-4 text-sm font-medium text-slate-500">Qeyd</h3><div class="prose-plain">{{ $post->notes }}</div>@endif

        @if ($post->status === 'sent')
          <div class="mt-6 space-y-3 border-t border-slate-100 pt-5" x-data="{ fb: false }">
            <form method="POST" action="{{ route('portal.posts.approve', $post) }}">@csrf<button class="btn-success w-full py-2.5"><x-icon name="check" class="size-5" /> Təsdiqlə</button></form>
            <button @click="fb = !fb" class="btn-secondary w-full"><x-icon name="chat" class="size-4" /> Rəy bildir / düzəliş istə</button>
            <form x-show="fb" x-cloak method="POST" action="{{ route('portal.posts.feedback', $post) }}" class="space-y-2">@csrf
              <textarea name="body" rows="4" required class="input" placeholder="Nəyi dəyişmək istəyirsiniz?"></textarea>
              <button class="btn-primary w-full">Göndər</button>
            </form>
          </div>
        @elseif ($post->status === 'client_approved')
          <p class="mt-6 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">✓ {{ Labels::date($post->client_decided_at, true) }} tarixində təsdiqlədiniz.</p>
        @endif
      </div>
      <div class="card p-6">
        <h3 class="mb-4 font-semibold">Rəylər</h3>
        <div class="space-y-4">
          @forelse ($comments as $c)
            <div class="flex gap-3"><x-avatar :user="$c->user" /><div class="flex-1"><p class="text-sm"><span class="font-medium">{{ $c->user?->name }}</span> <span class="text-xs text-slate-400">{{ Labels::date($c->created_at, true) }}</span></p><div class="prose-plain mt-1 rounded-lg {{ $c->user?->isClient() ? 'bg-violet-50' : 'bg-slate-50' }} px-3 py-2">{{ $c->body }}</div></div></div>
          @empty
            <p class="text-sm text-slate-500">Hələ rəy yoxdur.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</x-layouts.portal>
