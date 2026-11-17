<x-layouts.app :title="$brand->name.' — Fayllar'">
  @include('brands._header', ['section' => 'files'])

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
    <aside class="space-y-3">
      <div class="card p-2">
        <a href="{{ route('brands.files', $brand) }}" class="nav-link {{ ! $folder ? 'active' : '' }}"><x-icon name="grid" class="size-4" /> Əsas qovluq</a>
        @foreach ($folders as $f)
          <a href="{{ route('brands.files', [$brand, 'folder' => $f->id]) }}" class="nav-link {{ $folder?->id === $f->id ? 'active' : '' }}">
            <x-icon name="folder" class="size-4" /> <span class="flex-1 truncate">{{ $f->name }}</span>
            @unless ($f->client_visible)<x-icon name="eyeoff" class="size-3.5 text-slate-400" />@endunless
            <span class="text-xs text-slate-400">{{ $f->attachments_count }}</span>
          </a>
        @endforeach
      </div>
      <form method="POST" action="{{ route('brands.folders.store', $brand) }}" class="card space-y-2 p-3">
        @csrf
        <input name="name" required placeholder="Yeni qovluq adı" class="input">
        <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="client_visible" value="1" checked class="rounded border-slate-300 text-brand-600"> Müştəri görsün</label>
        <button class="btn-secondary btn-sm w-full"><x-icon name="plus" class="size-3.5" /> Qovluq yarat</button>
      </form>
      <p class="px-1 text-xs text-slate-500"><x-icon name="eye" class="inline size-3.5" /> Bu bölmədəki fayllar müştərinin “Media portfoliosu” bölməsində də görünür (gizlədilənlər istisna).</p>
    </aside>

    <div class="space-y-4 lg:col-span-3">
      <div class="card p-4">
        <div class="mb-3 flex flex-wrap items-center gap-2">
          <h2 class="flex-1 font-semibold text-slate-900">{{ $folder?->name ?? 'Əsas qovluq' }}</h2>
          @if ($folder)
            <div x-data="{ edit: false }" class="flex items-center gap-2">
              <button @click="edit = !edit" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-3.5" /> Qovluq</button>
              <form x-show="edit" x-cloak method="POST" action="{{ route('brands.folders.update', [$brand, $folder]) }}" class="flex items-center gap-2">
                @csrf @method('PUT')
                <input name="name" value="{{ $folder->name }}" class="input py-1">
                <label class="flex items-center gap-1 text-xs whitespace-nowrap"><input type="checkbox" name="client_visible" value="1" @checked($folder->client_visible) class="rounded border-slate-300 text-brand-600"> Müştəri görsün</label>
                <button class="btn-primary btn-sm">OK</button>
              </form>
              <form x-show="edit" x-cloak method="POST" action="{{ route('brands.folders.destroy', [$brand, $folder]) }}" onsubmit="return confirm('Qovluq silinsin?')">@csrf @method('DELETE')<button class="btn-danger btn-sm"><x-icon name="trash" class="size-3.5" /></button></form>
            </div>
          @endif
          <form class="w-48"><input type="hidden" name="folder" value="{{ $folder?->id }}"><input name="q" value="{{ request('q') }}" placeholder="Faylda axtar…" class="input py-1.5"></form>
        </div>
        <form method="POST" action="{{ route('brands.files.store', $brand) }}">
          @csrf <input type="hidden" name="folder_id" value="{{ $folder?->id }}">
          <x-uploader :brand="$brand" scope="files" :sub="$folder?->name" :autosubmit="true" label="Faylları bura atın (foto, video, PDF, brendbuk…)" />
        </form>
      </div>

      @if ($files->isEmpty())
        <div class="card"><x-empty icon="folder" title="Bu qovluq boşdur" text="Yuxarıdan fayl yükləyin." /></div>
      @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
          @foreach ($files as $f)
            <x-file-tile :file="$f">
              <form method="POST" action="{{ route('brands.files.toggle', [$brand, $f]) }}">@csrf @method('PATCH')
                <button class="rounded p-1 text-slate-500 hover:bg-slate-100" title="{{ $f->client_visible ? 'Müştəridən gizlət' : 'Müştəriyə göstər' }}"><x-icon :name="$f->client_visible ? 'eye' : 'eyeoff'" class="size-4" /></button>
              </form>
              @if (auth()->user()->isAdmin() || $f->uploaded_by === auth()->id())
                <form method="POST" action="{{ route('files.destroy', $f) }}" onsubmit="return confirm('Fayl silinsin?')">@csrf @method('DELETE')<button class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button></form>
              @endif
            </x-file-tile>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</x-layouts.app>
