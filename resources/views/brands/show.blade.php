@php use App\Support\Labels; $u = auth()->user(); @endphp
<x-layouts.app :title="$brand->name">
  @include('brands._header', ['section' => 'overview'])

  {{-- 4 əsas bölmə: qovluq görünüşü --}}
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @php
      $folders = [
        ['Tapşırıqlar', route('brands.tasks', $brand), 'tasks', $stats['open_tasks'].' aktiv'.($stats['overdue'] ? ' · '.$stats['overdue'].' gecikən' : ''), 'from-indigo-500 to-indigo-600', true],
        ['Fayllar', route('brands.files', $brand), 'folder', $stats['files'].' fayl · brendbuk, kataloq…', 'from-sky-500 to-sky-600', $u->hasPerm('brands.files')],
        ['Sosial Media Plan', route('brands.social', $brand), 'calendar', $stats['month_posts'].' paylaşım bu ay'.($stats['awaiting_client'] ? ' · '.$stats['awaiting_client'].' təsdiqdə' : ''), 'from-pink-500 to-rose-500', $u->hasPerm('brands.social')],
        ['Kontent Plan', route('brands.content', $brand), 'doc', $currentPlan ? Labels::monthYear($currentPlan->year, $currentPlan->month).': '.Labels::label(Labels::APPROVAL_STATUSES, $currentPlan->status) : Labels::monthYear(now()->year, now()->month).': yüklənməyib', 'from-amber-500 to-orange-500', $u->hasPerm('brands.content')],
      ];
    @endphp
    @foreach ($folders as [$label, $href, $icon, $meta, $grad, $show])
      @if ($show)
        <a href="{{ $href }}" class="group card relative overflow-hidden p-5 transition hover:-translate-y-0.5 hover:shadow-md">
          <span class="mb-4 inline-flex size-12 items-center justify-center rounded-xl bg-gradient-to-br {{ $grad }} text-white shadow-sm"><x-icon :name="$icon" class="size-6" /></span>
          <h3 class="font-semibold text-slate-900 group-hover:text-brand-700">{{ $label }}</h3>
          <p class="mt-0.5 text-sm text-slate-500">{{ $meta }}</p>
          <x-icon name="chev-right" class="absolute right-4 top-5 size-5 text-slate-300 group-hover:text-brand-500" />
        </a>
      @endif
    @endforeach
  </div>

  <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
      <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold">Yaxın paylaşımlar</h2></div>
      @forelse ($nextPosts as $p)
        <a href="{{ route('posts.show', $p) }}" class="flex items-center gap-3 border-b border-slate-50 px-5 py-3 last:border-0 hover:bg-slate-50">
          <span class="w-14 text-sm font-medium text-slate-700">{{ Labels::shortDate($p->publish_date) }}</span>
          <span class="rounded px-1.5 py-0.5 text-[11px] font-semibold text-white" style="background: {{ $p->typeColor() }}">{{ $p->typeLabel() }}</span>
          <span class="min-w-0 flex-1 truncate text-sm">{{ $p->title }}</span>
          <x-status :map="Labels::APPROVAL_STATUSES" :value="$p->status" />
        </a>
      @empty
        <x-empty icon="calendar" title="Planlaşdırılmış paylaşım yoxdur" />
      @endforelse
    </div>
    <div class="space-y-6">
      <div class="card p-5">
        <h2 class="mb-3 font-semibold">Müştəri hesabları</h2>
        @forelse ($brand->clientUsers as $cu)
          <div class="flex items-center gap-2 py-1.5 text-sm"><x-avatar :user="$cu" size="size-7" /><div class="min-w-0"><p class="truncate font-medium">{{ $cu->name }}</p><p class="truncate text-xs text-slate-500">{{ $cu->email }}</p></div></div>
        @empty
          <p class="text-sm text-slate-500">Bu markaya hələ müştəri hesabı təyin edilməyib.</p>
        @endforelse
        @if ($u->hasPerm('team'))<a href="{{ route('team.create', ['role' => 'client']) }}" class="btn-secondary btn-sm mt-3"><x-icon name="plus" class="size-3.5" /> Müştəri hesabı aç</a>@endif
      </div>
      <div class="card p-5">
        <h2 class="mb-3 font-semibold">Son fəaliyyətlər</h2>
        <ul class="space-y-3">
          @forelse ($activities as $a)
            <li class="text-sm"><span class="font-medium">{{ $a->user?->name ?? 'Sistem' }}</span> <span class="text-slate-600">{{ $a->description }}</span><div class="text-xs text-slate-400">{{ $a->created_at->diffForHumans() }}</div></li>
          @empty
            <li class="text-sm text-slate-500">Fəaliyyət yoxdur.</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</x-layouts.app>
