@php use App\Support\Labels; $u = auth()->user(); @endphp
<x-layouts.app :title="$post->title">
  @include('brands._header', ['section' => 'social'])
  <div class="mb-4"><a href="{{ route('brands.social', [$brand, 'month' => $post->publish_date->format('Y-m')]) }}" class="text-sm text-slate-500 hover:underline">← {{ Labels::monthYear($post->publish_date->year, $post->publish_date->month) }} təqviminə qayıt</a></div>

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
      <div class="card p-6">
        <div class="mb-4 flex flex-wrap items-center gap-2">
          <span class="rounded px-2 py-0.5 text-xs font-semibold text-white" style="background: {{ $post->typeColor() }}">{{ $post->typeLabel() }}</span>
          <h2 class="flex-1 text-lg font-semibold text-slate-900">{{ $post->title }}</h2>
          <x-status :map="Labels::APPROVAL_STATUSES" :value="$post->status" />
        </div>
        @if ($post->attachments->isNotEmpty())
          <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($post->attachments as $f)
              <x-file-tile :file="$f">
                @if ($u->isAdmin() || $f->uploaded_by === $u->id)
                  <form method="POST" action="{{ route('files.destroy', $f) }}" onsubmit="return confirm('Fayl silinsin?')">@csrf @method('DELETE')<button class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button></form>
                @endif
              </x-file-tile>
            @endforeach
          </div>
        @endif
        <form method="POST" action="{{ route('posts.files', $post) }}" class="mb-5">@csrf
          <x-uploader :brand="$brand" scope="posts" :sub="$post->publish_date->format('Y-m')" :autosubmit="true" compact label="Vizual əlavə et" accept="image/*,video/*,.pdf" />
        </form>
        <h3 class="mb-1 text-sm font-medium text-slate-500">Paylaşım mətni</h3>
        <div class="prose-plain rounded-lg bg-slate-50 p-4">{{ $post->caption ?: '—' }}</div>
        @if ($post->notes)<h3 class="mb-1 mt-4 text-sm font-medium text-slate-500">Qeyd</h3><div class="prose-plain">{{ $post->notes }}</div>@endif
      </div>

      <div class="card p-6">
        <h2 class="mb-4 font-semibold">Şərhlər və rəylər</h2>
        <x-comments :comments="$post->comments" :action="route('posts.comment', $post)" :client-toggle="$post->isClientVisible()" />
      </div>
    </div>

    <div class="space-y-6">
      <div class="card space-y-3 p-5">
        <h3 class="font-semibold">Təsdiq prosesi</h3>
        @php
          $steps = [
            ['Hazırlanır', in_array($post->status, ['draft', 'internal_changes'])],
            ['Rəhbər təsdiqi', $post->status === 'pending'],
            ['Müştəri təsdiqi', in_array($post->status, ['sent', 'client_changes'])],
            ['Təsdiqləndi', $post->status === 'client_approved'],
            ['Paylaşıldı', $post->status === 'published'],
          ];
          $cur = collect($steps)->search(fn ($s) => $s[1]);
        @endphp
        <ol class="space-y-2">
          @foreach ($steps as $i => [$label])
            <li class="flex items-center gap-2 text-sm {{ $i === $cur ? 'font-semibold text-slate-900' : ($i < $cur ? 'text-emerald-700' : 'text-slate-400') }}">
              <span class="inline-flex size-5 items-center justify-center rounded-full text-[10px] {{ $i < $cur ? 'bg-emerald-100' : ($i === $cur ? 'bg-brand-600 text-white' : 'bg-slate-100') }}">@if($i < $cur)<x-icon name="check" class="size-3" />@else{{ $i + 1 }}@endif</span>{{ $label }}
            </li>
          @endforeach
        </ol>

        <div class="space-y-2 border-t border-slate-100 pt-3">
          @if (in_array($post->status, ['draft', 'internal_changes', 'client_changes']))
            <form method="POST" action="{{ route('posts.submit', $post) }}">@csrf<button class="btn-primary w-full"><x-icon name="send" class="size-4" /> Rəhbər təsdiqinə göndər</button></form>
          @endif
          @if ($u->hasPerm('approve') && in_array($post->status, ['draft', 'pending', 'internal_changes', 'client_changes']))
            <form method="POST" action="{{ route('posts.approve', $post) }}">@csrf<button class="btn-success w-full"><x-icon name="check" class="size-4" /> Təsdiqlə və müştəriyə göndər</button></form>
          @endif
          @if ($u->hasPerm('approve') && $post->status === 'pending')
            <form method="POST" action="{{ route('posts.reject', $post) }}" x-data="{ o: false }">@csrf
              <button type="button" @click="o = !o" class="btn-danger w-full">Düzəliş istə</button>
              <div x-show="o" x-cloak class="mt-2 space-y-2"><textarea name="body" rows="3" required class="input" placeholder="Nəyi düzəltmək lazımdır?"></textarea><button class="btn-primary btn-sm w-full">Göndər</button></div>
            </form>
          @endif
          @if ($post->status === 'sent')
            <form method="POST" action="{{ route('posts.remind', $post) }}">@csrf<button class="btn-secondary w-full"><x-icon name="bell" class="size-4" /> Müştəriyə xatırlat</button></form>
            <p class="text-xs text-slate-500">Avtomatik xatırlatma: {{ $post->reminder_count }} dəfə{{ $post->last_reminder_at ? ', son: '.$post->last_reminder_at->diffForHumans() : '' }}</p>
          @endif
          @if ($post->status === 'client_approved')
            <form method="POST" action="{{ route('posts.published', $post) }}">@csrf<button class="btn-primary w-full">Paylaşıldı kimi qeyd et</button></form>
          @endif
        </div>
      </div>

      <div class="card p-5">
        <dl class="space-y-2.5 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500">Tarix</dt><dd class="font-medium">{{ Labels::date($post->publish_date) }}{{ $post->publish_time ? ', '.$post->publish_time : '' }}</dd></div>
          <div class="flex justify-between gap-2"><dt class="text-slate-500">Platformalar</dt><dd class="text-right">{{ collect($post->platforms)->map(fn ($x) => Labels::PLATFORMS[$x] ?? $x)->implode(', ') ?: '—' }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Hazırlayan</dt><dd>{{ $post->creator?->name ?? '—' }}</dd></div>
          @if ($post->client_decided_at)<div class="flex justify-between"><dt class="text-slate-500">Müştəri qərarı</dt><dd>{{ Labels::date($post->client_decided_at, true) }}</dd></div>@endif
        </dl>
        <div class="mt-4 flex gap-2 border-t border-slate-100 pt-4">
          <a href="{{ route('posts.edit', $post) }}" class="btn-secondary flex-1"><x-icon name="pencil" class="size-4" /> Redaktə</a>
          <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('Paylaşım silinsin?')">@csrf @method('DELETE')<button class="btn-danger"><x-icon name="trash" class="size-4" /></button></form>
        </div>
      </div>
    </div>
  </div>
</x-layouts.app>
