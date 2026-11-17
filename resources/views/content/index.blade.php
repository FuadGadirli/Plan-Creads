@php use App\Support\Labels; @endphp
<x-layouts.app :title="$brand->name.' — Kontent Plan'">
  @include('brands._header', ['section' => 'content'])
  <p class="mb-4 text-sm text-slate-500">Hər ay üçün kontent planı PDF və ya PPTX kimi yükləyin. Rəhbər təsdiqlədikdən sonra plan müştərinin panelində görünür. Keçmiş aylar açıq rənglə göstərilir.</p>
  @include('partials.months-grid', ['statusMap' => Labels::APPROVAL_STATUSES, 'url' => fn ($m) => route('content.show', [$brand, $year, $m])])
</x-layouts.app>
