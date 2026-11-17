@php use App\Support\Labels; @endphp
<x-layouts.app :title="$client->exists ? 'Müştərini redaktə et' : 'Yeni müştəri'">
  <x-page-header :title="$client->exists ? $client->name : 'Yeni müştəri'"><x-slot:breadcrumb><a href="{{ route('clients.index') }}" class="hover:underline">Əməkdaşlıq</a></x-slot:breadcrumb></x-page-header>
  <form method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}" class="card max-w-3xl space-y-5 p-6">
    @csrf @if ($client->exists) @method('PUT') @endif
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <x-field label="Şirkətin adı" name="name" class="sm:col-span-2"><input name="name" value="{{ old('name', $client->name) }}" required class="input"></x-field>
      <x-field label="Sahə" name="industry"><input name="industry" value="{{ old('industry', $client->industry) }}" class="input" placeholder="Məs: Restoran, Retail"></x-field>
      <x-field label="Vebsayt" name="website"><input name="website" value="{{ old('website', $client->website) }}" class="input" placeholder="https://"></x-field>
      <x-field label="Məsul şəxs" name="contact_person"><input name="contact_person" value="{{ old('contact_person', $client->contact_person) }}" class="input"></x-field>
      <x-field label="Vəzifəsi" name="contact_position"><input name="contact_position" value="{{ old('contact_position', $client->contact_position) }}" class="input"></x-field>
      <x-field label="E-poçt" name="email"><input type="email" name="email" value="{{ old('email', $client->email) }}" class="input"></x-field>
      <x-field label="Telefon" name="phone"><input name="phone" value="{{ old('phone', $client->phone) }}" class="input"></x-field>
      <x-field label="Ünvan" name="address" class="sm:col-span-2"><input name="address" value="{{ old('address', $client->address) }}" class="input"></x-field>
      <x-field label="Status" name="status"><select name="status" class="input">@foreach (Labels::CLIENT_STATUSES_CRM as $k => [$l])<option value="{{ $k }}" @selected(old('status', $client->status) === $k)>{{ $l }}</option>@endforeach</select></x-field>
      <x-field label="Agentlikdə məsul" name="owner_id"><select name="owner_id" class="input"><option value="">—</option>@foreach ($staff as $s)<option value="{{ $s->id }}" @selected(old('owner_id', $client->owner_id) == $s->id)>{{ $s->name }}</option>@endforeach</select></x-field>
      <x-field label="İlk əlaqə tarixi" name="first_contact_at"><input type="date" name="first_contact_at" value="{{ old('first_contact_at', $client->first_contact_at?->toDateString()) }}" class="input"></x-field>
      <x-field label="Əməkdaşlığın başlama tarixi" name="started_at"><input type="date" name="started_at" value="{{ old('started_at', $client->started_at?->toDateString()) }}" class="input"></x-field>
      <x-field label="Qeydlər" name="notes" class="sm:col-span-2"><textarea name="notes" rows="4" class="input">{{ old('notes', $client->notes) }}</textarea></x-field>
    </div>
    <div class="flex gap-2"><button class="btn-primary">Yadda saxla</button><a href="{{ $client->exists ? route('clients.show', $client) : route('clients.index') }}" class="btn-secondary">Ləğv et</a></div>
  </form>
</x-layouts.app>
