<x-layouts.portal title="Media portfoliosu">
  <x-page-header title="Media portfoliosu" subtitle="Agentliyin sizin üçün hazırladığı foto, video və materiallar. Yükləmədən baxa bilərsiniz." />
  <div class="mb-5 flex flex-wrap gap-2">
    <a href="{{ route('portal.media') }}" class="btn btn-sm {{ ! $folder ? 'bg-slate-900 text-white' : 'bg-white ring-1 ring-slate-200' }}"><x-icon name="grid" class="size-4" /> Əsas</a>
    @foreach ($folders as $f)
      <a href="{{ route('portal.media', ['folder' => $f->id]) }}" class="btn btn-sm {{ $folder?->id === $f->id ? 'bg-slate-900 text-white' : 'bg-white ring-1 ring-slate-200' }}"><x-icon name="folder" class="size-4" /> {{ $f->name }} <span class="opacity-60">{{ $f->attachments_count }}</span></a>
    @endforeach
  </div>
  @if ($files->isEmpty())
    <div class="card"><x-empty icon="photo" title="Bu qovluqda fayl yoxdur" /></div>
  @else
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
      @foreach ($files as $f)<x-file-tile :file="$f" />@endforeach
    </div>
  @endif
</x-layouts.portal>
