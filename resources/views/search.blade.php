<x-layouts.app title="Axtarış">
  <x-page-header :title="$q ? '“'.$q.'” üzrə nəticələr' : 'Axtarış'" subtitle="Tapşırıqlar, markalar, kontent planlar, paylaşımlar, fayllar və müştərilər arasında axtarın. Məs: “Marka noyabr”." />
  <form class="mb-6 max-w-xl"><input name="q" value="{{ $q }}" autofocus class="input text-base" placeholder="Axtar…"></form>
  @if ($q && empty($results))
    <div class="card"><x-empty icon="search" title="Heç nə tapılmadı" text="Başqa söz yoxlayın." /></div>
  @endif
  <div class="space-y-6">
    @foreach ($results as $group => $items)
      <div class="card">
        <div class="border-b border-slate-100 px-5 py-3 text-sm font-semibold text-slate-700">{{ $group }} <span class="text-slate-400">({{ $items->count() }})</span></div>
        @foreach ($items as $r)
          <a href="{{ $r['url'] }}" class="flex items-center justify-between gap-3 border-b border-slate-50 px-5 py-3 last:border-0 hover:bg-slate-50">
            <span class="truncate text-sm font-medium text-slate-900">{{ $r['title'] }}</span><span class="shrink-0 text-xs text-slate-500">{{ $r['meta'] }}</span>
          </a>
        @endforeach
      </div>
    @endforeach
  </div>
</x-layouts.app>
