<x-layouts.portal title="Şirkət ayarları">
  <x-page-header title="Şirkət ayarları" />
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="card p-6">
      <h2 class="mb-4 font-semibold">Şirkət</h2>
      <div class="flex items-center gap-4"><x-brand-logo :brand="$brand" size="size-16" text="text-xl" /><div><p class="text-lg font-semibold">{{ $brand->name }}</p><p class="text-sm text-slate-500">{{ $user->client?->name }}</p></div></div>
      @if ($user->client)
        <dl class="mt-5 space-y-2 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500">Vebsayt</dt><dd>{{ $user->client->website ?: '—' }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Əməkdaşlıq başlayıb</dt><dd>{{ \App\Support\Labels::date($user->client->started_at) }}</dd></div>
        </dl>
      @endif
      <p class="mt-5 text-xs text-slate-500">Şirkət məlumatlarında dəyişiklik üçün agentliklə əlaqə saxlayın.</p>
    </div>
    <div class="space-y-6">
      <form method="POST" action="{{ route('portal.settings.profile') }}" class="card space-y-4 p-6">@csrf @method('PUT')
        <h2 class="font-semibold">Mənim məlumatlarım</h2>
        <x-field label="Ad, soyad" name="name"><input name="name" value="{{ old('name', $user->name) }}" required class="input"></x-field>
        <div class="grid grid-cols-2 gap-4">
          <x-field label="Vəzifə" name="position"><input name="position" value="{{ old('position', $user->position) }}" class="input"></x-field>
          <x-field label="Telefon" name="phone"><input name="phone" value="{{ old('phone', $user->phone) }}" class="input"></x-field>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="email_notifications" value="1" @checked($user->email_notifications) class="rounded border-slate-300 text-brand-600"> Bildirişləri mail ilə al</label>
        <button class="btn-primary">Yadda saxla</button>
      </form>
      <form method="POST" action="{{ route('portal.settings.password') }}" class="card space-y-4 p-6">@csrf @method('PUT')
        <h2 class="font-semibold">Şifrəni dəyiş</h2>
        <x-field label="Hazırkı şifrə" name="current_password"><input type="password" name="current_password" required class="input"></x-field>
        <div class="grid grid-cols-2 gap-4">
          <x-field label="Yeni şifrə" name="password"><input type="password" name="password" required class="input"></x-field>
          <x-field label="Təkrar"><input type="password" name="password_confirmation" required class="input"></x-field>
        </div>
        <button class="btn-primary">Şifrəni dəyiş</button>
      </form>
    </div>
  </div>
</x-layouts.portal>
