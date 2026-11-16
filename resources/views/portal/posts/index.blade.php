<x-layouts.portal title="Paylaşımlar">
  <x-page-header title="Paylaşımlar" subtitle="Bu ay nə və nə vaxt paylaşılacaq — hamısı burada.">
    <x-slot:actions>
      @if ($awaiting > 1)
        <form method="POST" action="{{ route('portal.posts.approveAll') }}" onsubmit="return confirm('Gözləyən {{ $awaiting }} paylaşımın hamısı təsdiqlənsin?')">@csrf<button class="btn-success"><x-icon name="check" class="size-4" /> Gözləyən {{ $awaiting }} paylaşımı təsdiqlə</button></form>
      @endif
    </x-slot:actions>
  </x-page-header>
  @include('partials.calendar', ['client' => true, 'postUrl' => fn ($p) => route('portal.posts.show', $p), 'addUrl' => null])
</x-layouts.portal>
