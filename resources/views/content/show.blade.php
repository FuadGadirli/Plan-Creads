@php use App\Support\Labels; $u = auth()->user(); $latest = $versions->first(); @endphp
<x-layouts.app :title="$brand->name.' — '.Labels::monthYear($year, $month)">
  @include('brands._header', ['section' => 'content'])
  <div class="mb-4 flex flex-wrap items-center gap-3">
    <a href="{{ route('brands.content', [$brand, 'year' => $year]) }}" class="text-sm text-slate-500 hover:underline">← {{ $year }} kontent planları</a>
    <h2 class="text-xl font-semibold text-slate-900">{{ Labels::monthYear($year, $month) }} kontent planı</h2>
    @if ($plan->exists)<x-status :map="Labels::APPROVAL_STATUSES" :value="$plan->status" />@endif
  </div>

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
      @if ($latest)
        <div class="card overflow-hidden">
          <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-5 py-3">
            <span class="rounded-md bg-slate-900 px-2 py-0.5 text-xs font-bold text-white">v{{ $latest->version }}</span>
            <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $latest->attachment?->original_name }}</span>
            <x-status :map="Labels::APPROVAL_STATUSES" :value="$latest->status" />
            @if ($latest->attachment)<a href="{{ $latest->attachment->previewUrl() }}" class="btn-secondary btn-sm"><x-icon name="eye" class="size-3.5" /> Tam ekran</a>@endif
          </div>
          @if ($latest->attachment)
            @include('files._embed', ['file' => $latest->attachment, 'height' => 'h-[560px]'])
          @endif
          @if ($latest->note)<div class="border-t border-slate-100 px-5 py-3 text-sm text-slate-600"><span class="font-medium">Qeyd:</span> {{ $latest->note }}</div>@endif
        </div>

        @if ($u->hasPerm('approve') && in_array($latest->status, ['pending', 'internal_changes']))
          <div class="card flex flex-wrap items-center gap-3 p-4" x-data="{ o: false }">
            <div class="flex-1 space-y-1"><p class="text-sm text-slate-600">Rəhbər olaraq bu versiyanı təsdiqləsəniz, dərhal müştərinin panelinə düşəcək və müştəriyə mail gedəcək.</p>@include('partials.recipients', ['brand' => $brand])</div>
            <form method="POST" action="{{ route('content.approve', $latest) }}">@csrf<button class="btn-success"><x-icon name="check" class="size-4" /> Təsdiqlə və müştəriyə göndər</button></form>
            @if ($latest->status === 'pending')
              <button @click="o = !o" class="btn-danger">Düzəliş istə</button>
              <form x-show="o" x-cloak method="POST" action="{{ route('content.reject', $latest) }}" class="w-full space-y-2">@csrf
                <textarea name="body" rows="3" required class="input" placeholder="Nəyi düzəltmək lazımdır?"></textarea><button class="btn-primary btn-sm">Göndər</button>
              </form>
            @endif
          </div>
        @endif
        @if ($plan->status === 'sent')
          <form method="POST" action="{{ route('content.remind', $plan) }}" class="card flex items-center gap-3 p-4">@csrf
            <div class="flex-1 space-y-1"><p class="text-sm text-slate-600">Müştəri təsdiqi gözlənilir. Xatırlatma göndərmək istəyirsiniz?</p>@include('partials.recipients', ['brand' => $brand])</div>
            <button class="btn-secondary"><x-icon name="bell" class="size-4" /> Müştəriyə xatırlat</button>
          </form>
        @endif
      @endif

      <div class="card p-5">
        <h3 class="mb-1 font-semibold">{{ $latest ? 'Yeni versiya yüklə' : 'Kontent planı yüklə' }}</h3>
        <p class="mb-4 text-sm text-slate-500">PDF və ya PPTX. {{ $latest ? 'Yeni versiya v'.($latest->version + 1).' kimi saxlanacaq, köhnə versiyalar itmir.' : '' }}</p>
        <form method="POST" action="{{ route('content.upload', [$brand, $year, $month]) }}" class="space-y-3">
          @csrf
          <x-uploader :brand="$brand" scope="content" :sub="sprintf('%d-%02d', $year, $month)" :multiple="false" accept=".pdf,.ppt,.pptx,.key,.doc,.docx" label="PDF / PPTX faylını bura atın" />
          <textarea name="note" rows="2" class="input" placeholder="Qeyd (məs: müştərinin rəyinə əsasən 3-cü və 7-ci postlar dəyişdirildi)"></textarea>
          <div class="flex flex-wrap gap-2">
            <button name="action" value="submit" class="btn-primary"><x-icon name="send" class="size-4" /> Yüklə və rəhbər təsdiqinə göndər</button>
            @if ($u->hasPerm('approve'))<button name="action" value="send" class="btn-success"><x-icon name="check" class="size-4" /> Yüklə və birbaşa müştəriyə göndər</button>@endif
          </div>
        </form>
      </div>
    </div>

    <div class="space-y-6">
      <div class="card p-5">
        <h3 class="mb-3 font-semibold">Versiyalar</h3>
        @forelse ($versions as $v)
          <div class="flex items-start gap-3 border-b border-slate-50 py-2.5 last:border-0">
            <span class="rounded-md {{ $loop->first ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600' }} px-2 py-0.5 text-xs font-bold">v{{ $v->version }}</span>
            <div class="min-w-0 flex-1">
              @if ($v->attachment)<a href="{{ $v->attachment->previewUrl() }}" class="block truncate text-sm font-medium hover:text-brand-700">{{ $v->attachment->original_name }}</a>@endif
              <p class="text-xs text-slate-500">{{ $v->uploader?->name }} · {{ Labels::date($v->created_at, true) }}</p>
              <x-status class="mt-1" :map="Labels::APPROVAL_STATUSES" :value="$v->status" />
            </div>
          </div>
        @empty
          <p class="text-sm text-slate-500">Hələ heç bir versiya yüklənməyib.</p>
        @endforelse
      </div>
      @if ($plan->exists)
        <div class="card p-5">
          <h3 class="mb-4 font-semibold">Rəylər və şərhlər</h3>
          <x-comments :comments="$comments" :action="route('content.comment', $plan)" :client-toggle="$plan->clientVersions()->exists()" />
        </div>
      @endif
    </div>
  </div>
</x-layouts.app>
