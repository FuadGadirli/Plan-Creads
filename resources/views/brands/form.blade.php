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
    <x-field label="Qısa məlumat" name="description"><textarea name="description" rows="4" class="input">{{ old('description', $brand->description) }}</textarea></x-field>
    @if ($brand->exists)
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($brand->is_active) class="rounded border-slate-300 text-brand-600"> Marka aktivdir</label>
    @endif
    <div class="flex gap-2"><button class="btn-primary">Yadda saxla</button><a href="{{ $brand->exists ? route('brands.show', $brand) : route('brands.index') }}" class="btn-secondary">Ləğv et</a></div>
  </form>
</x-layouts.app>
