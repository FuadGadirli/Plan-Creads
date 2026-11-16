@php use App\Support\Labels; $editing = $post->exists; @endphp
<x-layouts.app :title="$editing ? 'Paylaşımı redaktə et' : 'Yeni paylaşım'">
  @include('brands._header', ['section' => 'social'])
  <form method="POST" action="{{ $editing ? route('posts.update', $post) : route('posts.store', $brand) }}" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="card space-y-5 p-6 lg:col-span-2">
      <h2 class="text-lg font-semibold">{{ $editing ? 'Paylaşımı redaktə et' : 'Yeni paylaşım' }}</h2>
      @if ($editing && in_array($post->status, ['sent', 'client_approved']))
        <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 ring-1 ring-amber-200">Bu paylaşım artıq müştəriyə göndərilib. Mətn, tarix və ya vizual dəyişsə, yenidən təsdiq tələb olunacaq.</div>
      @endif
      <x-field label="Başlıq (daxili ad)" name="title"><input name="title" value="{{ old('title', $post->title) }}" required class="input" placeholder="Məs: Yeni il kampaniyası — teaser"></x-field>
      <x-field label="Paylaşım mətni (caption)" name="caption"><textarea name="caption" rows="8" class="input" placeholder="Instagram/Facebook-da paylaşılacaq mətn, hashtaglər…">{{ old('caption', $post->caption) }}</textarea></x-field>
      <x-field label="Vizuallar (şəkil, video, karusel)" hint="Story üçün şəkil/video, karusel üçün bir neçə şəkil yükləyin. Böyük videolar da dəstəklənir.">
        @if ($editing && $post->attachments->isNotEmpty())
          <div class="mb-3 grid grid-cols-3 gap-3 sm:grid-cols-4">@foreach ($post->attachments as $f)<x-file-tile :file="$f" :actions="false" />@endforeach</div>
        @endif
        <x-uploader :brand="$brand" scope="posts" :sub="$post->publish_date?->format('Y-m')" accept="image/*,video/*,.pdf" />
      </x-field>
      <x-field label="Əlavə qeyd (müştəri də görür)" name="notes"><textarea name="notes" rows="3" class="input" placeholder="Məs: Story-də link stikeri olacaq">{{ old('notes', $post->notes) }}</textarea></x-field>
    </div>
    <div class="space-y-6">
      <div class="card space-y-4 p-6">
        <div class="grid grid-cols-2 gap-3">
          <x-field label="Tarix" name="publish_date"><input type="date" name="publish_date" value="{{ old('publish_date', $post->publish_date?->toDateString()) }}" required class="input"></x-field>
          <x-field label="Saat" name="publish_time"><input type="time" name="publish_time" value="{{ old('publish_time', $post->publish_time) }}" class="input"></x-field>
        </div>
        <x-field label="Növ" name="type">
          <div class="grid grid-cols-2 gap-2">
            @foreach (Labels::POST_TYPES as $k => [$l, $c])
              <label class="cursor-pointer"><input type="radio" name="type" value="{{ $k }}" @checked(old('type', $post->type) === $k) class="peer sr-only">
                <span class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-slate-200 peer-checked:ring-2 peer-checked:ring-brand-500"><span class="size-3 rounded-sm" style="background: {{ $c }}"></span>{{ $l }}</span></label>
            @endforeach
          </div>
        </x-field>
        <x-field label="Platformalar" name="platforms">
          <div class="flex flex-wrap gap-1.5">
            @foreach (Labels::PLATFORMS as $k => $l)
              <label class="cursor-pointer"><input type="checkbox" name="platforms[]" value="{{ $k }}" @checked(in_array($k, old('platforms', $post->platforms ?? []))) class="peer sr-only">
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200 peer-checked:bg-slate-900 peer-checked:text-white">{{ $l }}</span></label>
            @endforeach
          </div>
        </x-field>
      </div>
      <div class="card space-y-2 p-4">
        <button name="action" value="draft" class="btn-secondary w-full">Qaralama kimi saxla</button>
        <button name="action" value="submit" class="btn-primary w-full"><x-icon name="send" class="size-4" /> Rəhbər təsdiqinə göndər</button>
        @if (auth()->user()->hasPerm('approve'))
          <button name="action" value="send" class="btn-success w-full"><x-icon name="check" class="size-4" /> Təsdiqlə və müştəriyə göndər</button>
        @endif
      </div>
    </div>
  </form>
</x-layouts.app>
