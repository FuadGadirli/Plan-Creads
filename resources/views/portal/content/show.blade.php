@php use App\Support\Labels; $latest = $versions->first(); $previous = $versions->skip(1)->first(); @endphp
<x-layouts.portal :title="$plan->title().' kontent planı'">
  <div class="mb-4"><a href="{{ route('portal.content', ['year' => $plan->year]) }}" class="text-sm text-slate-500 hover:underline">← Bütün aylar</a></div>
  <x-page-header :title="$plan->title().' kontent planı'">
    <x-slot:actions><x-status :map="Labels::CLIENT_STATUSES" :value="$latest->status" class="!text-sm" /></x-slot:actions>
  </x-page-header>

  @if ($latest->status === 'sent')
    <div class="card mb-6 flex flex-wrap items-center gap-3 border-l-4 border-l-brand-500 p-4" x-data="{ fb: false }">
      <div class="flex-1"><p class="font-medium">Bu plan təsdiqinizi gözləyir</p><p class="text-sm text-slate-500">Bəyənirsinizsə təsdiqləyin. Dəyişiklik istəyirsinizsə rəyinizi yazın — yenilənmiş versiya hazır olanda sizə bildiriş gələcək.</p></div>
      <form method="POST" action="{{ route('portal.content.approve', $latest) }}">@csrf<button class="btn-success"><x-icon name="check" class="size-4" /> Təsdiqlə</button></form>
      <button @click="fb = !fb" class="btn-secondary"><x-icon name="chat" class="size-4" /> Rəy bildir</button>
      <form x-show="fb" x-cloak method="POST" action="{{ route('portal.content.feedback', $latest) }}" class="w-full space-y-2">@csrf
        <textarea name="body" rows="4" required class="input" placeholder="Məs: 3-cü postda məhsulun qiymətini göstərməyək, 5-ci post üçün başqa ideya istərdik…"></textarea>
        <button class="btn-primary"><x-icon name="send" class="size-4" /> Rəyi göndər</button>
      </form>
    </div>
  @elseif ($latest->status === 'client_approved')
    <div class="mb-6 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">✓ Bu planı {{ Labels::date($latest->client_decided_at, true) }} tarixində təsdiqləmisiniz.</div>
  @elseif ($latest->status === 'client_changes')
    <div class="mb-6 rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-200">Rəyiniz agentliyə çatdırılıb. Yenilənmiş versiya hazır olanda burada görünəcək.</div>
  @endif

  <div class="grid grid-cols-1 gap-6 {{ $previous ? 'xl:grid-cols-2' : '' }}">
    @foreach (array_filter([$latest, $previous]) as $v)
      <div class="card overflow-hidden {{ $loop->first ? 'ring-2 ring-brand-500' : 'opacity-90' }}">
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
          <span class="rounded-md {{ $loop->first ? 'bg-brand-600' : 'bg-slate-400' }} px-2 py-0.5 text-xs font-bold text-white">{{ $loop->first ? 'YENİ' : 'KÖHNƏ' }} · v{{ $v->version }}</span>
          <span class="min-w-0 flex-1 truncate text-sm">{{ Labels::date($v->reviewed_at ?? $v->created_at) }}</span>
          @if ($v->attachment)<a href="{{ $v->attachment->previewUrl() }}" class="btn-secondary btn-sm"><x-icon name="eye" class="size-3.5" /> Tam ekran</a>@endif
        </div>
        @if ($v->attachment) @include('files._embed', ['file' => $v->attachment, 'height' => 'h-[600px]']) @endif
        @if ($v->note && $loop->first)<div class="border-t border-slate-100 px-5 py-3 text-sm text-slate-600"><span class="font-medium">Agentliyin qeydi:</span> {{ $v->note }}</div>@endif
      </div>
    @endforeach
  </div>

  @if ($versions->count() > 2)
    <div class="card mt-6 p-5"><h3 class="mb-2 font-semibold">Bütün versiyalar</h3>
      @foreach ($versions as $v)<a href="{{ $v->attachment?->previewUrl() }}" class="mr-3 text-sm text-brand-600 hover:underline">v{{ $v->version }}</a>@endforeach
    </div>
  @endif

  <div class="card mt-6 p-5">
    <h3 class="mb-4 font-semibold">Rəylər</h3>
    <div class="space-y-4">
      @forelse ($comments as $c)
        <div class="flex gap-3"><x-avatar :user="$c->user" />
          <div class="flex-1"><p class="text-sm"><span class="font-medium">{{ $c->user?->name }}</span> <span class="text-xs text-slate-400">{{ Labels::date($c->created_at, true) }}</span></p>
            <div class="prose-plain mt-1 rounded-lg {{ $c->user?->isClient() ? 'bg-violet-50' : 'bg-slate-50' }} px-3 py-2">{{ $c->body }}</div></div></div>
      @empty
        <p class="text-sm text-slate-500">Hələ rəy yoxdur.</p>
      @endforelse
    </div>
  </div>
</x-layouts.portal>
