@if (session('success'))
  <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition class="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
    <x-icon name="check" class="size-4" /> {{ session('success') }}
  </div>
@endif
@if ($errors->any())
  <div class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200">
    <div class="flex items-center gap-2 font-medium"><x-icon name="alert" class="size-4" /> Xəta baş verdi</div>
    <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif
