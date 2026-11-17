@php use App\Support\Labels; @endphp
<x-layouts.portal title="Ana səhifə">
  <x-page-header :title="$brand->name" :subtitle="Labels::monthYear(now()->year, now()->month).' — ümumi baxış'" />
  <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <x-stat label="Bu ay planlaşdırılan paylaşım" :value="$stats['month_total']" icon="calendar" :href="route('portal.posts')" />
    <x-stat label="Təsdiqinizi gözləyir" :value="$stats['awaiting']" icon="clock" tone="amber" :href="route('portal.posts', ['view' => 'list'])" />
    <x-stat label="Bu ay təsdiqlənib" :value="$stats['approved']" icon="check" tone="emerald" />
    <x-stat label="Media portfoliosunda fayl" :value="$stats['files']" icon="photo" tone="violet" :href="route('portal.media')" />
  </div>

  @foreach ($plansAwaiting as $pa)
    <a href="{{ route('portal.content.show', $pa) }}" class="mt-6 flex items-center gap-4 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 p-5 text-white shadow-lg hover:opacity-95">
      <x-icon name="doc" class="size-10" />
      <div class="flex-1"><p class="text-lg font-semibold">{{ $pa->title() }} kontent planı təsdiqinizi gözləyir</p><p class="text-sm text-white/80">Baxın, təsdiqləyin və ya rəyinizi yazın — çəkiliş və dizayn işləri təsdiqdən sonra başlayır.</p></div>
      <span class="btn bg-white text-brand-700">Bax →</span>
    </a>
  @endforeach

  <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="card">
      <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h2 class="font-semibold">Təsdiqinizi gözləyən paylaşımlar</h2>
        @if ($stats['awaiting'] > 1)
          <form method="POST" action="{{ route('portal.posts.approveAll') }}" onsubmit="return confirm('Gözləyən bütün paylaşımlar təsdiqlənsin?')">@csrf<button class="btn-success btn-sm"><x-icon name="check" class="size-4" /> Hamısını təsdiqlə</button></form>
        @endif
      </div>
      @forelse ($awaitingPosts as $p)
        <a href="{{ route('portal.posts.show', $p) }}" class="flex items-center gap-3 border-b border-slate-50 px-5 py-3 last:border-0 hover:bg-slate-50">
          @php $img = $p->attachments->first(fn ($a) => $a->thumbUrl()); @endphp
          @if ($img)<img src="{{ $img->thumbUrl() }}" class="size-12 rounded-lg object-cover" alt="" onerror="this.remove()">@else<span class="inline-flex size-12 items-center justify-center rounded-lg text-white" style="background: {{ $p->typeColor() }}"><x-icon name="photo" /></span>@endif
          <div class="min-w-0 flex-1"><p class="truncate font-medium">{{ $p->title }}</p><p class="text-sm text-slate-500">{{ $p->typeLabel() }} · {{ Labels::date($p->publish_date) }}</p></div>
          <x-icon name="chev-right" class="size-4 text-slate-400" />
        </a>
      @empty
        <x-empty icon="check" title="Təsdiq gözləyən paylaşım yoxdur" text="Hər şey qaydasındadır!" />
      @endforelse
    </div>
    <div class="card">
      <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold">Yaxın paylaşımlar</h2></div>
      @forelse ($upcoming as $p)
        <a href="{{ route('portal.posts.show', $p) }}" class="flex items-center gap-3 border-b border-slate-50 px-5 py-3 last:border-0 hover:bg-slate-50">
          <span class="w-12 text-center"><span class="block text-lg font-semibold leading-none">{{ $p->publish_date->day }}</span><span class="text-[11px] uppercase text-slate-500">{{ mb_substr(Labels::MONTHS[$p->publish_date->month], 0, 3) }}</span></span>
          <span class="size-2.5 rounded-full" style="background: {{ $p->typeColor() }}"></span>
          <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $p->title }}</span>
          <x-status :map="Labels::CLIENT_STATUSES" :value="$p->status" />
        </a>
      @empty
        <x-empty icon="calendar" title="Yaxın paylaşım yoxdur" />
      @endforelse
    </div>
  </div>
</x-layouts.portal>
