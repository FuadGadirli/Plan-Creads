@php use App\Support\Labels; $u = auth()->user(); $h = (int) now()->format('G'); $greet = $h < 12 ? 'Sabahınız xeyir' : ($h < 18 ? 'Günortanız xeyir' : 'Axşamınız xeyir'); @endphp
<x-layouts.app title="Ana səhifə">
  <x-page-header :title="$greet.', '.explode(' ', $u->name)[0].'!'" :subtitle="Labels::date(now()).' · Bu gün nələr var — bir baxışda.'">
    <x-slot:actions>
      <a href="{{ route('tasks.mine') }}" class="btn-secondary"><x-icon name="inbox" class="size-4" /> Mənim işlərim</a>
    </x-slot:actions>
  </x-page-header>

  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Aktiv tapşırıq" :value="$stats['active']" icon="tasks" :href="route('tasks.index')" />
    <x-stat label="Gecikən tapşırıq" :value="$stats['overdue']" icon="alert" tone="rose" :href="route('tasks.index')" />
    <x-stat label="Bu həftə deadline" :value="$stats['week']" icon="clock" tone="amber" />
    <x-stat label="Bu ay bitirildi" :value="$stats['done_month']" icon="check" tone="emerald" />
  </div>

  @if ($u->hasPerm('brands.social') || $u->hasPerm('approve'))
  <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Rəhbər təsdiqi gözləyir" :value="$stats['pending_internal']" icon="approve" tone="amber" :href="$u->hasPerm('approve') ? route('approvals.index') : null" />
    <x-stat label="Müştəri təsdiqi gözləyən paylaşım" :value="$stats['awaiting_client_posts']" icon="calendar" tone="blue" :href="$u->hasPerm('approve') ? route('approvals.index') : null" />
    <x-stat label="Müştəri təsdiqi gözləyən kontent plan" :value="$stats['awaiting_client_plans']" icon="doc" tone="violet" />
    <x-stat label="Müştəri düzəliş istədi" :value="$stats['client_changes']" icon="chat" tone="rose" :href="$u->hasPerm('approve') ? route('approvals.index') : null" />
  </div>
  @endif

  <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="card xl:col-span-2">
      <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h2 class="font-semibold text-slate-900">Mənim tapşırıqlarım</h2>
        <a href="{{ route('tasks.mine') }}" class="text-sm text-brand-600 hover:underline">Hamısı →</a>
      </div>
      @forelse ($myTasks as $t)
        <a href="{{ route('tasks.show', $t) }}" class="flex items-center gap-3 border-b border-slate-50 px-5 py-3 last:border-0 hover:bg-slate-50">
          <x-brand-logo :brand="$t->brand" size="size-8" text="text-[10px]" />
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-slate-800">{{ $t->title }}</p>
            <p class="text-xs text-slate-500">{{ $t->brand?->name ?? 'Ümumi' }}</p>
          </div>
          <x-status :map="Labels::TASK_STATUSES" :value="$t->status" />
          <span class="w-20 text-right text-xs {{ $t->isOverdue() ? 'font-semibold text-rose-600' : 'text-slate-500' }}">{{ $t->due_date ? Labels::shortDate($t->due_date) : '—' }}</span>
        </a>
      @empty
        <x-empty icon="check" title="Hər şey qaydasındadır" text="Sizə aid açıq tapşırıq yoxdur." />
      @endforelse
    </div>

    <div class="card">
      <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900">Tapşırıqlar statusa görə</h2></div>
      @php $total = max(1, $byStatus->sum()); @endphp
      <div class="space-y-4 p-5">
        @foreach (Labels::TASK_STATUSES as $key => [$label, $color])
          @php $n = $byStatus[$key] ?? 0; $bar = ['slate' => 'bg-slate-400', 'blue' => 'bg-blue-500', 'amber' => 'bg-amber-500', 'emerald' => 'bg-emerald-500'][$color]; @endphp
          <div>
            <div class="mb-1 flex justify-between text-sm"><span class="text-slate-600">{{ $label }}</span><span class="font-medium text-slate-900">{{ $n }}</span></div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $bar }}" style="width: {{ round($n / $total * 100) }}%"></div></div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    @if ($workload->isNotEmpty())
    <div class="card">
      <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900">Komandanın iş yükü</h2></div>
      <ul class="divide-y divide-slate-50">
        @foreach ($workload as $w)
          <li><a href="{{ route('tasks.index', ['user' => $w->id]) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-slate-50">
            <x-avatar :user="$w" />
            <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-800">{{ $w->name }}</p><p class="truncate text-xs text-slate-500">{{ $w->position }}</p></div>
            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-sm font-semibold text-slate-700">{{ $w->open_tasks }}</span>
          </a></li>
        @endforeach
      </ul>
    </div>
    @endif

    @if ($u->hasPerm('brands.social'))
    <div class="card">
      <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900">Yaxın 7 günün paylaşımları</h2></div>
      @forelse ($upcomingPosts as $p)
        <a href="{{ route('posts.show', $p) }}" class="flex items-center gap-3 border-b border-slate-50 px-5 py-2.5 last:border-0 hover:bg-slate-50">
          <span class="w-12 shrink-0 text-center"><span class="block text-lg font-semibold leading-none text-slate-900">{{ $p->publish_date->day }}</span><span class="text-[11px] uppercase text-slate-500">{{ mb_substr(Labels::MONTHS[$p->publish_date->month], 0, 3) }}</span></span>
          <span class="size-2 shrink-0 rounded-full" style="background: {{ $p->typeColor() }}"></span>
          <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-800">{{ $p->title }}</p><p class="text-xs text-slate-500">{{ $p->brand->name }} · {{ $p->typeLabel() }}</p></div>
          <x-status :map="Labels::APPROVAL_STATUSES" :value="$p->status" />
        </a>
      @empty
        <x-empty icon="calendar" title="Yaxın günlərdə paylaşım yoxdur" />
      @endforelse
    </div>
    @endif

    <div class="card">
      <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900">Son fəaliyyətlər</h2></div>
      <ul class="space-y-3 p-5">
        @forelse ($activities as $a)
          <li class="flex gap-3 text-sm">
            <x-avatar :user="$a->user" size="size-7" />
            <div class="min-w-0"><p class="text-slate-700"><span class="font-medium text-slate-900">{{ $a->user?->name ?? 'Sistem' }}</span> {{ $a->description }}</p>
              <p class="text-xs text-slate-400">{{ $a->brand?->name ? $a->brand->name.' · ' : '' }}{{ $a->created_at->diffForHumans() }}</p></div>
          </li>
        @empty
          <li class="text-sm text-slate-500">Hələ fəaliyyət yoxdur.</li>
        @endforelse
      </ul>
    </div>
  </div>
</x-layouts.app>
