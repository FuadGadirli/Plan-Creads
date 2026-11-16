@php
  $u = auth()->user();
  $tabs = [
    ['overview', 'Ümumi', 'brands.show', 'grid', true],
    ['tasks', 'Tapşırıqlar', 'brands.tasks', 'tasks', true],
    ['files', 'Fayllar', 'brands.files', 'folder', $u->hasPerm('brands.files')],
    ['social', 'Sosial Media Plan', 'brands.social', 'calendar', $u->hasPerm('brands.social')],
    ['content', 'Kontent Plan', 'brands.content', 'doc', $u->hasPerm('brands.content')],
  ];
@endphp
<div class="mb-6">
  <div class="mb-1 text-sm text-slate-500"><a href="{{ route('brands.index') }}" class="hover:underline">Markalar</a></div>
  <div class="flex flex-wrap items-center gap-4">
    <x-brand-logo :brand="$brand" size="size-14" text="text-lg" />
    <div class="min-w-0 flex-1">
      <h1 class="truncate text-2xl font-semibold tracking-tight text-slate-900">{{ $brand->name }}</h1>
      <p class="text-sm text-slate-500">{{ $brand->client?->name ? 'Müştəri: '.$brand->client->name : 'Marka' }} @unless($brand->is_active) · <span class="text-rose-600">Deaktiv</span> @endunless</p>
    </div>
    @if ($u->hasPerm('brands.manage'))<a href="{{ route('brands.edit', $brand) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Redaktə</a>@endif
  </div>
  <nav class="-mb-px mt-5 flex gap-6 overflow-x-auto border-b border-slate-200">
    @foreach ($tabs as [$key, $label, $route, $icon, $show])
      @if ($show)<a href="{{ route($route, $brand) }}" class="tab {{ $section === $key ? 'active' : '' }}"><x-icon :name="$icon" class="size-4" /> {{ $label }}</a>@endif
    @endforeach
  </nav>
</div>
