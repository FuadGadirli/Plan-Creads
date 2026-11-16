{{-- Faylı səhifənin içində göstərir (yükləmədən). $file, $height --}}
@php
  $kind = $file->kind(); $height = $height ?? 'h-[70vh]'; $embed = $embed ?? null;
  if (! $embed && $file->disk === 'onedrive' && in_array($kind, ['pdf', 'slides', 'doc', 'sheet'])) {
      try { $embed = \App\Services\OneDrive::previewUrl($file->remote_id); } catch (\Throwable) { $embed = null; }
  }
@endphp
<div class="bg-slate-100">
  @if ($kind === 'image')
    <img src="{{ $file->url() }}" alt="{{ $file->original_name }}" class="mx-auto max-h-[80vh] w-auto object-contain">
  @elseif ($kind === 'video')
    <video src="{{ $file->url() }}" controls preload="metadata" class="mx-auto max-h-[80vh] w-full bg-black"></video>
  @elseif ($kind === 'audio')
    <div class="p-6"><audio src="{{ $file->url() }}" controls class="w-full"></audio></div>
  @elseif ($embed)
    <iframe src="{{ $embed }}" class="{{ $height }} w-full border-0" allowfullscreen></iframe>
  @elseif ($kind === 'pdf')
    <iframe src="{{ $file->url() }}" class="{{ $height }} w-full border-0"></iframe>
  @else
    <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
      <x-icon name="doc" class="size-12 text-slate-400" />
      <p class="text-sm text-slate-600">Bu fayl növü üçün ön baxış {{ $file->disk === 'onedrive' ? 'mövcud deyil' : 'OneDrive qoşulduqdan sonra işləyəcək' }}.</p>
      <a href="{{ route('files.show', [$file, 'download' => 1]) }}" class="btn-secondary"><x-icon name="download" class="size-4" /> Yüklə</a>
    </div>
  @endif
</div>
