{{-- Fayl yükləyici. Formanın içində istifadə olunur; hazır faylların id-ləri attachment_ids[] kimi göndərilir. --}}
@props(['brandSelect' => null, 'brand' => null, 'scope' => 'files', 'sub' => null, 'multiple' => true, 'autosubmit' => false, 'accept' => '', 'label' => 'Faylları bura atın və ya seçin', 'compact' => false])
@php
  $cfg = [
    'startUrl' => route('uploads.start'),
    'chunkUrl' => route('uploads.chunk', '__ID__'),
    'brandId' => $brand?->id,
    'brandSelect' => $brandSelect,
    'scope' => $scope,
    'sub' => $sub,
    'multiple' => $multiple,
    'autosubmit' => $autosubmit,
  ];
@endphp
<div x-data="uploader(@js($cfg))" {{ $attributes }}>
  <input type="file" x-ref="input" class="hidden" @if($multiple) multiple @endif @if($accept) accept="{{ $accept }}" @endif @change="add($event.target.files)">
  <template x-for="id in ids" :key="id"><input type="hidden" name="attachment_ids[]" :value="id"></template>

  <div @click="pick()" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)"
       :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-slate-300 hover:border-brand-400 hover:bg-slate-50'"
       class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border-2 border-dashed {{ $compact ? 'px-3 py-3' : 'px-4 py-6' }} text-center transition">
    <x-icon name="upload" class="size-5 text-slate-400" />
    <span class="text-sm text-slate-600">{{ $label }}</span>
  </div>

  <ul class="mt-2 space-y-1.5" x-show="files.length" x-cloak>
    <template x-for="(f, i) in files" :key="i">
      <li class="rounded-lg bg-slate-50 px-3 py-2 text-sm ring-1 ring-slate-200">
        <div class="flex items-center gap-2">
          <span class="min-w-0 flex-1 truncate font-medium text-slate-700" x-text="f.name"></span>
          <span class="shrink-0 text-xs text-slate-500" x-text="human(f.size)"></span>
          <template x-if="f.status === 'done'"><span class="text-emerald-600"><x-icon name="check" class="size-4" /></span></template>
          <button type="button" class="text-slate-400 hover:text-rose-600" x-show="f.status !== 'uploading'" @click.stop="remove(i)"><x-icon name="x" class="size-4" /></button>
        </div>
        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-200" x-show="f.status === 'uploading' || f.status === 'waiting'">
          <div class="h-full rounded-full bg-brand-500 transition-all" :style="`width: ${f.progress}%`"></div>
        </div>
        <p class="mt-1 text-xs text-rose-600" x-show="f.error" x-text="f.error"></p>
      </li>
    </template>
  </ul>
  <p class="mt-1 text-xs text-amber-700" x-show="busy" x-cloak>Yüklənir… Səhifəni bağlamayın.</p>
</div>
