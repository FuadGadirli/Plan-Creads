{{-- Müştəriyə gedən bildirişin kimə çatacağını göstərir. $brand --}}
@php $rcpt = $brand->clientUsers()->where('is_active', true)->get(); @endphp
@if ($rcpt->isEmpty())
  <div class="flex flex-wrap items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 ring-1 ring-amber-200">
    <x-icon name="alert" class="size-4" /> Bu markanın müştəri hesabı yoxdur, bildirişlər heç kimə getməyəcək.
    @if (auth()->user()->hasPerm('brands.manage'))<a href="{{ route('brands.edit', $brand) }}#contacts" class="font-medium underline">Müştəri əlavə et</a>@endif
  </div>
@else
  <p class="flex flex-wrap items-center gap-1 text-xs text-slate-500"><x-icon name="mail" class="size-3.5" /> Müştəri bildirişi gedəcək: <span class="font-medium text-slate-700">{{ $rcpt->map(fn ($u) => $u->name.' ('.$u->email.')')->implode(', ') }}</span></p>
@endif
