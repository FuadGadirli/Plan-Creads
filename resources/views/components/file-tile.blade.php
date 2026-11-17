@props(['file', 'actions' => true])
@php
  $kind = $file->kind();
  $icons = ['image' => 'photo', 'video' => 'video', 'pdf' => 'doc', 'slides' => 'doc', 'doc' => 'doc', 'sheet' => 'doc', 'archive' => 'folder', 'design' => 'sparkles'];
  $tones = ['image' => 'text-sky-500', 'video' => 'text-rose-500', 'pdf' => 'text-red-500', 'slides' => 'text-orange-500', 'doc' => 'text-blue-500', 'sheet' => 'text-emerald-500', 'design' => 'text-violet-500'];
  $thumb = $file->thumbUrl();
@endphp
<div class="group card overflow-hidden">
  <a href="{{ $file->previewUrl() }}" class="relative block aspect-[4/3] bg-slate-100">
    <span class="absolute inset-0 flex items-center justify-center {{ $tones[$kind] ?? 'text-slate-400' }}"><x-icon :name="$icons[$kind] ?? 'doc'" class="size-10" /></span>
    @if ($thumb)
      <img src="{{ $thumb }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover" onerror="this.remove()">
    @endif
    @if ($kind === 'video')
      <span class="absolute bottom-2 left-2 rounded-md bg-black/60 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-white">Video</span>
    @endif
    @if (isset($file->client_visible) && ! $file->client_visible)
      <span class="absolute right-2 top-2 rounded-md bg-white/90 p-1 text-slate-600" title="Müştəri görmür"><x-icon name="eyeoff" class="size-3.5" /></span>
    @endif
  </a>
  <div class="flex items-start gap-2 p-3">
    <div class="min-w-0 flex-1">
      <a href="{{ $file->previewUrl() }}" class="block truncate text-sm font-medium text-slate-800 hover:text-brand-700" title="{{ $file->original_name }}">{{ $file->original_name }}</a>
      <p class="text-xs text-slate-500">{{ $file->sizeLabel() }} · {{ \App\Support\Labels::shortDate($file->created_at) }}</p>
    </div>
    @if ($actions)
      <div class="flex shrink-0 items-center opacity-70 group-hover:opacity-100">
        <a href="{{ route('files.show', [$file, 'download' => 1]) }}" class="rounded p-1 text-slate-500 hover:bg-slate-100" title="Yüklə"><x-icon name="download" class="size-4" /></a>
        {{ $slot }}
      </div>
    @endif
  </div>
</div>
