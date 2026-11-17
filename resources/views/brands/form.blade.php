<x-layouts.app :title="$brand->exists ? 'Markanı redaktə et' : 'Yeni marka'">
  <x-page-header :title="$brand->exists ? $brand->name.' — redaktə' : 'Yeni marka'">
    <x-slot:breadcrumb><a href="{{ route('brands.index') }}" class="hover:underline">Markalar</a></x-slot:breadcrumb>
  </x-page-header>
  <form method="POST" enctype="multipart/form-data" action="{{ $brand->exists ? route('brands.update', $brand) : route('brands.store') }}" class="card max-w-2xl space-y-5 p-6">
    @csrf @if ($brand->exists) @method('PUT') @endif
    <x-field label="Markanın adı" name="name"><input id="name" name="name" value="{{ old('name', $brand->name) }}" required class="input"></x-field>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <x-field label="Loqo" name="logo" hint="PNG, JPG, SVG — maks. 4 MB">
        <div class="flex items-center gap-3">
          @if ($brand->logoUrl())<x-brand-logo :brand="$brand" size="size-12" />@endif
          <input type="file" name="logo" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium">
        </div>
      </x-field>
      <x-field label="Marka rəngi" name="color">
        <input type="color" name="color" value="{{ old('color', $brand->color) }}" class="h-10 w-20 cursor-pointer rounded-lg border-0 ring-1 ring-slate-200">
      </x-field>
    </div>
    <x-field label="Müştəri (Əməkdaşlıq)" name="client_id" hint="Markanı CRM-dəki müştəri ilə əlaqələndirin.">
      <select name="client_id" class="input">
        <option value="">— Seçilməyib —</option>
        @foreach ($clients as $c)<option value="{{ $c->id }}" @selected(old('client_id', $brand->client_id) == $c->id)>{{ $c->name }}</option>@endforeach
      </select>
    </x-field>
    @unless ($brand->exists)
      <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
        <p class="font-medium text-slate-900">Müştəri hesabı</p>
        <p class="mb-3 text-sm text-slate-500">Müştəri bu maillə platformaya daxil olacaq, təsdiqə göndərdiklərinizi görəcək və bildirişləri alacaq. Ona şifrə təyin etmə linki mail ilə göndəriləcək. Boş saxlasanız, sonra da əlavə edə bilərsiniz.</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <x-field label="Müştəri tərəfdən məsul şəxs" name="contact_name"><input name="contact_name" value="{{ old('contact_name') }}" class="input" placeholder="Məs: Aysel Məmmədova"></x-field>
          <x-field label="Onun e-poçtu" name="contact_email"><input type="email" name="contact_email" value="{{ old('contact_email') }}" class="input" placeholder="marketing@marka.az"></x-field>
        </div>
      </div>
    @endunless
    <x-field label="Qısa məlumat" name="description"><textarea name="description" rows="4" class="input">{{ old('description', $brand->description) }}</textarea></x-field>
    @if ($brand->exists)
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($brand->is_active) class="rounded border-slate-300 text-brand-600"> Marka aktivdir</label>
    @endif
    <div class="flex gap-2"><button class="btn-primary">Yadda saxla</button><a href="{{ $brand->exists ? route('brands.show', $brand) : route('brands.index') }}" class="btn-secondary">Ləğv et</a></div>
  </form>

  @if ($brand->exists)
    <div id="contacts" class="card mt-6 max-w-2xl space-y-4 p-6">
      <div>
        <h2 class="font-semibold text-slate-900">Müştəri hesabları</h2>
        <p class="text-sm text-slate-500">Bu şəxslər markanın müştəri panelinə daxil olur, təsdiqə göndərilənləri görür və bütün bildirişləri (xatırlatmalar daxil) mail ilə alır.</p>
      </div>
      @forelse ($brand->clientUsers as $cu)
        <div class="flex flex-wrap items-center gap-3 rounded-lg p-3 ring-1 ring-slate-200 {{ $cu->is_active ? '' : 'opacity-60' }}">
          <x-avatar :user="$cu" />
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-slate-900">{{ $cu->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ $cu->email }} · {{ $cu->last_login_at ? 'Son giriş: '.$cu->last_login_at->diffForHumans() : 'Hələ daxil olmayıb' }}</p>
          </div>
          <form method="POST" action="{{ route('brands.contacts.invite', [$brand, $cu]) }}">@csrf<button class="btn-ghost btn-sm" title="Dəvət mailini yenidən göndər"><x-icon name="mail" class="size-4" /> Dəvət</button></form>
          <form method="POST" action="{{ route('brands.contacts.destroy', [$brand, $cu]) }}" onsubmit="return confirm('{{ $cu->email }} bu markadan çıxarılsın?')">@csrf @method('DELETE')<button class="btn-danger btn-sm"><x-icon name="trash" class="size-4" /></button></form>
        </div>
      @empty
        <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">Bu markaya hələ müştəri hesabı əlavə edilməyib. Təsdiq bildirişləri və xatırlatmalar heç kimə getməyəcək.</div>
      @endforelse
      <form method="POST" action="{{ route('brands.contacts.store', $brand) }}" class="grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 sm:grid-cols-5">
        @csrf
        <input name="contact_name" required class="input sm:col-span-2" placeholder="Ad, soyad">
        <input type="email" name="contact_email" required class="input sm:col-span-2" placeholder="E-poçt">
        <button class="btn-primary"><x-icon name="plus" class="size-4" /> Əlavə et</button>
      </form>
    </div>
  @endif
</x-layouts.app>
