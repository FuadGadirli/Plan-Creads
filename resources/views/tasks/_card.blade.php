@php use App\Support\Labels; @endphp
<a href="{{ route('tasks.show', $t) }}" data-id="{{ $t->id }}" class="block cursor-grab rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-200 transition hover:ring-brand-300 active:cursor-grabbing">
  <div class="mb-2 flex items-center gap-2">
    @if ($t->brand)<span class="inline-flex items-center gap-1.5 truncate text-xs font-medium text-slate-500"><span class="size-2 rounded-full" style="background: {{ $t->brand->color }}"></span>{{ $t->brand->name }}</span>@endif
    @if (in_array($t->priority, ['high', 'urgent']))<x-status class="ml-auto" :map="Labels::PRIORITIES" :value="$t->priority" />@endif
  </div>
  <p class="text-sm font-medium text-slate-900">{{ $t->title }}</p>
  <div class="mt-3 flex items-center gap-2">
    <div class="flex -space-x-1.5">@foreach ($t->users->take(3) as $p)<x-avatar :user="$p" size="size-6" />@endforeach</div>
    <span class="ml-auto flex items-center gap-2 text-xs {{ $t->isOverdue() ? 'font-semibold text-rose-600' : 'text-slate-500' }}">
      @if ($t->comments_count)<span class="inline-flex items-center gap-0.5 text-slate-400"><x-icon name="chat" class="size-3.5" />{{ $t->comments_count }}</span>@endif
      @if ($t->due_date)<span class="inline-flex items-center gap-0.5"><x-icon name="clock" class="size-3.5" />{{ Labels::shortDate($t->due_date) }}</span>@endif
    </span>
  </div>
</a>
