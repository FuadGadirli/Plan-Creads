@php use App\Support\Labels; @endphp
<x-layouts.portal title="Aylıq kontent plan">
  <x-page-header title="Aylıq kontent plan" subtitle="Hər ayın kontent planı. Keçmiş aylar açıq, cari ay tünd rənglə göstərilir." />
  @include('partials.months-grid', ['statusMap' => Labels::CLIENT_STATUSES, 'url' => fn ($m) => isset($plans[$m]) ? route('portal.content.show', $plans[$m]) : null])
</x-layouts.portal>
