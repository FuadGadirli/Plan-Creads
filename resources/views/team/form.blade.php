@php use App\Support\Labels; $editing = $user->exists; $me = auth()->user(); @endphp
<x-layouts.app :title="$editing ? $user->name : 'Yeni istifadəçi'">
  <x-page-header :title="$editing ? $user->name : 'Yeni istifadəçi'"><x-slot:breadcrumb><a href="{{ route('team.index') }}" class="hover:underline">İşçilər və icazələr</a></x-slot:breadcrumb></x-page-header>
  <form method="POST" action="{{ $editing ? route('team.update', $user) : route('team.store') }}" x-data="{ role: '{{ old('role', $user->role) }}' }" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="card space-y-4 p-6">
      <x-field label="Hesab növü" name="role">
        <div class="grid grid-cols-3 gap-2">
          @foreach (Labels::ROLES as $k => $l)
            @if ($k !== 'admin' || $me->isAdmin())
              <label class="cursor-pointer"><input type="radio" name="role" value="{{ $k }}" x-model="role" class="peer sr-only"><span class="block rounded-lg px-2 py-2 text-center text-sm font-medium ring-1 ring-slate-200 peer-checked:bg-brand-600 peer-checked:text-white peer-checked:ring-brand-600">{{ $l }}</span></label>
            @endif
          @endforeach
        </div>
      </x-field>
      <x-field label="Ad, soyad" name="name"><input name="name" value="{{ old('name', $user->name) }}" required class="input"></x-field>
      <x-field label="E-poçt (giriş üçün)" name="email"><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input"></x-field>
      <x-field label="Vəzifə" name="position"><input name="position" value="{{ old('position', $user->position) }}" class="input" placeholder="Məs: SMM menecer, Dizayner"></x-field>
      <x-field label="Telefon" name="phone"><input name="phone" value="{{ old('phone', $user->phone) }}" class="input"></x-field>
      <x-field label="{{ $editing ? 'Yeni şifrə (dəyişmək üçün)' : 'Şifrə' }}" name="password" hint="{{ $editing ? 'Boş saxlasanız şifrə dəyişməyəcək.' : 'Boş saxlasanız, istifadəçiyə şifrə təyin etmə linki mail ilə göndəriləcək.' }}">
        <input type="password" name="password" class="input" autocomplete="new-password">
      </x-field>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="send_invite" value="1" class="rounded border-slate-300 text-brand-600"> Dəvət mailini göndər</label>
      @if ($editing)
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-slate-300 text-brand-600"> Hesab aktivdir</label>
      @else <input type="hidden" name="is_active" value="1"> @endif
    </div>

    <div class="space-y-6 lg:col-span-2">
      <div class="card p-6" x-show="role === 'employee'">
        <h3 class="font-semibold">Giriş icazələri</h3>
        <p class="mb-4 text-sm text-slate-500">İşçi yalnız seçilmiş bölmələri görəcək. Özünə təyin edilmiş tapşırıqları isə həmişə görür.</p>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
          @foreach (Labels::PERMISSIONS as $k => [$l, $d])
            <label class="flex cursor-pointer gap-3 rounded-lg p-3 ring-1 ring-slate-200 hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-300">
              <input type="checkbox" name="permissions[]" value="{{ $k }}" @checked(in_array($k, old('permissions', $user->permissions ?? []))) class="mt-0.5 rounded border-slate-300 text-brand-600">
              <span><span class="block text-sm font-medium text-slate-900">{{ $l }}</span><span class="block text-xs text-slate-500">{{ $d }}</span></span>
            </label>
          @endforeach
        </div>
      </div>
      <div class="card p-6" x-show="role === 'admin'" x-cloak>
        <h3 class="font-semibold">Rəhbərlik hesabı</h3>
        <p class="text-sm text-slate-500">Rəhbərlik platformanın bütün bölmələrini görür və bütün əməliyyatları edə bilir.</p>
      </div>
      <div class="card space-y-4 p-6" x-show="role === 'client'" x-cloak>
        <div><h3 class="font-semibold">Müştəri hesabı</h3><p class="text-sm text-slate-500">Müştəri yalnız seçilmiş markaların təsdiqlənmiş kontent planlarını, paylaşımlarını və media portfoliosunu görür. Tapşırıqları və daxili şərhləri görmür.</p></div>
        <x-field label="Şirkət (Əməkdaşlıq)" name="client_id">
          <select name="client_id" class="input"><option value="">—</option>@foreach ($clients as $c)<option value="{{ $c->id }}" @selected(old('client_id', $user->client_id) == $c->id)>{{ $c->name }}</option>@endforeach</select>
        </x-field>
        <div>
          <p class="label">Görə biləcəyi markalar</p>
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            @forelse ($brands as $b)
              <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2.5 ring-1 ring-slate-200 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-300">
                <input type="checkbox" name="brand_ids[]" value="{{ $b->id }}" @checked(in_array($b->id, old('brand_ids', $userBrands))) class="rounded border-slate-300 text-brand-600">
                <x-brand-logo :brand="$b" size="size-7" text="text-[10px]" /><span class="text-sm font-medium">{{ $b->name }}</span>
              </label>
            @empty
              <p class="text-sm text-slate-500">Əvvəlcə marka yaradın.</p>
            @endforelse
          </div>
        </div>
      </div>
      <div class="flex gap-2"><button class="btn-primary">Yadda saxla</button><a href="{{ route('team.index') }}" class="btn-secondary">Ləğv et</a></div>
    </div>
  </form>
</x-layouts.app>
