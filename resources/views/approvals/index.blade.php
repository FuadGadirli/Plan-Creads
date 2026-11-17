@php use App\Support\Labels; @endphp
<x-layouts.app title="Təsdiq gözləyənlər">
  <x-page-header title="Təsdiq gözləyənlər" subtitle="Komandanın göndərdiyi kontent planlar və paylaşımlar. Təsdiqlədiyiniz an müştərinin panelinə düşür." />

  <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="card">
      <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4"><x-icon name="doc" class="size-5 text-amber-500" /><h2 class="font-semibold">Kontent planlar</h2><span class="ml-auto rounded-full bg-amber-100 px-2 text-sm font-semibold text-amber-800">{{ $versions->count() }}</span></div>
      @forelse ($versions as $v)
        <a href="{{ route('content.show', [$v->plan->brand, $v->plan->year, $v->plan->month]) }}" class="flex items-center gap-3 border-b border-slate-50 px-5 py-3 last:border-0 hover:bg-slate-50">
          <x-brand-logo :brand="$v->plan->brand" size="size-9" text="text-[10px]" />
          <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ $v->plan->brand->name }} — {{ $v->plan->title() }} <span class="text-slate-400">v{{ $v->version }}</span></p><p class="text-xs text-slate-500">{{ $v->uploader?->name }} · {{ $v->created_at->diffForHumans() }}</p></div>
          <x-icon name="chev-right" class="size-4 text-slate-400" />
        </a>
      @empty
        <x-empty icon="check" title="Gözləyən kontent plan yoxdur" />
      @endforelse
    </div>

    <div class="card">
      <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4"><x-icon name="calendar" class="size-5 text-amber-500" /><h2 class="font-semibold">Paylaşımlar</h2><span class="ml-auto rounded-full bg-amber-100 px-2 text-sm font-semibold text-amber-800">{{ $posts->count() }}</span></div>
      @forelse ($posts as $p)
        <div class="flex items-center gap-3 border-b border-slate-50 px-5 py-3 last:border-0">
          @php $img = $p->attachments->first(fn ($a) => $a->thumbUrl()); @endphp
          @if ($img)<img src="{{ $img->thumbUrl() }}" class="size-11 rounded-lg object-cover" alt="" onerror="this.remove()">@else<span class="inline-flex size-11 items-center justify-center rounded-lg text-white" style="background: {{ $p->typeColor() }}"><x-icon name="photo" class="size-5" /></span>@endif
          <a href="{{ route('posts.show', $p) }}" class="min-w-0 flex-1 hover:underline"><p class="truncate text-sm font-medium">{{ $p->title }}</p><p class="text-xs text-slate-500">{{ $p->brand->name }} · {{ $p->typeLabel() }} · {{ Labels::date($p->publish_date) }}</p></a>
          <form method="POST" action="{{ route('posts.approve', $p) }}">@csrf<button class="btn-success btn-sm" title="Təsdiqlə və müştəriyə göndər"><x-icon name="check" class="size-4" /> Təsdiqlə</button></form>
        </div>
      @empty
        <x-empty icon="check" title="Gözləyən paylaşım yoxdur" />
      @endforelse
    </div>

    <div class="card">
      <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4"><x-icon name="chat" class="size-5 text-rose-500" /><h2 class="font-semibold">Müştəri düzəliş istədi</h2></div>
      @forelse ($planChanges as $v)
        <a href="{{ route('content.show', [$v->plan->brand, $v->plan->year, $v->plan->month]) }}" class="flex items-center gap-3 border-b border-slate-50 px-5 py-3 hover:bg-slate-50"><x-badge color="rose">Kontent plan</x-badge><span class="truncate text-sm">{{ $v->plan->brand->name }} — {{ $v->plan->title() }}</span></a>
      @empty @endforelse
      @forelse ($clientChanges as $p)
        <a href="{{ route('posts.show', $p) }}" class="flex items-center gap-3 border-b border-slate-50 px-5 py-3 hover:bg-slate-50"><x-badge color="rose">Paylaşım</x-badge><span class="truncate text-sm">{{ $p->brand->name }} — {{ $p->title }} ({{ Labels::shortDate($p->publish_date) }})</span></a>
      @empty
        @if ($planChanges->isEmpty())<x-empty icon="check" title="Müştəridən düzəliş tələbi yoxdur" />@endif
      @endforelse
    </div>

    <div class="card">
      <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4"><x-icon name="clock" class="size-5 text-blue-500" /><h2 class="font-semibold">Müştəri təsdiqini gözləyir</h2><span class="ml-auto rounded-full bg-blue-100 px-2 text-sm font-semibold text-blue-800">{{ $awaitingClient->count() }}</span></div>
      @forelse ($awaitingClient as $p)
        <div class="flex items-center gap-3 border-b border-slate-50 px-5 py-2.5 last:border-0">
          <a href="{{ route('posts.show', $p) }}" class="min-w-0 flex-1 hover:underline"><p class="truncate text-sm">{{ $p->brand->name }} — {{ $p->title }}</p><p class="text-xs text-slate-500">{{ Labels::date($p->publish_date) }} · göndərilib {{ $p->sent_at?->diffForHumans() }}</p></a>
          <form method="POST" action="{{ route('posts.remind', $p) }}">@csrf<button class="btn-ghost btn-sm" title="Xatırlat"><x-icon name="bell" class="size-4" /></button></form>
        </div>
      @empty
        <x-empty icon="check" title="Hamısı təsdiqlənib" />
      @endforelse
    </div>
  </div>
</x-layouts.app>
