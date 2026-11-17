@php use App\Support\Labels; @endphp
<x-layouts.app :title="$brand->name.' — Sosial Media Plan'">
  @include('brands._header', ['section' => 'social'])

  <div class="mb-4 flex flex-wrap items-center gap-2">
    @foreach (['draft', 'pending', 'sent', 'client_changes', 'client_approved', 'published'] as $s)
      @if ($counts[$s] ?? 0)<x-status :map="Labels::APPROVAL_STATUSES" :value="$s" class="!text-sm">: {{ $counts[$s] }}</x-status>@endif
    @endforeach
    <div class="ml-auto flex gap-2">
      @if ($counts['sent'] ?? 0)
        <form method="POST" action="{{ route('brands.social.remind', $brand) }}">@csrf<button class="btn-secondary"><x-icon name="bell" class="size-4" /> Müştəriyə xatırlat</button></form>
      @endif
      <a href="{{ route('posts.create', $brand) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Paylaşım əlavə et</a>
    </div>
  </div>

  <div class="mb-4">@include('partials.recipients', ['brand' => $brand])</div>

  @include('partials.calendar', [
    'postUrl' => fn ($p) => route('posts.show', $p),
    'addUrl' => fn ($d) => route('posts.create', [$brand, 'date' => $d->toDateString()]),
  ])
</x-layouts.app>
