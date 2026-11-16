@php $layout = auth()->user()->isClient() ? 'layouts.portal' : 'layouts.app'; @endphp
<x-dynamic-component :component="$layout" :title="$file->original_name">
  <div class="mb-4 flex flex-wrap items-center gap-3">
    <a href="{{ $back }}" class="btn-secondary px-2"><x-icon name="chev-left" class="size-4" /></a>
    <div class="min-w-0 flex-1">
      <h1 class="truncate text-lg font-semibold text-slate-900">{{ $file->original_name }}</h1>
      <p class="text-xs text-slate-500">{{ $file->sizeLabel() }} · {{ \App\Support\Labels::date($file->created_at, true) }}{{ $file->uploader ? ' · '.$file->uploader->name : '' }}</p>
    </div>
    <a href="{{ route('files.show', [$file, 'download' => 1]) }}" class="btn-secondary"><x-icon name="download" class="size-4" /> Yüklə</a>
  </div>
  <div class="card overflow-hidden">
    @include('files._embed', ['file' => $file, 'embed' => $embed, 'height' => 'h-[78vh]'])
  </div>
</x-dynamic-component>
