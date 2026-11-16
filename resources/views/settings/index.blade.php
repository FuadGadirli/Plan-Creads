@php use App\Models\Setting; use App\Support\Labels; @endphp
<x-layouts.app title="Ayarlar">
  <x-page-header title="Ayarlar" />
  <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="space-y-6">
      <form method="POST" action="{{ route('settings.profile') }}" enctype="multipart/form-data" class="card space-y-4 p-6">
        @csrf @method('PUT')
        <h2 class="font-semibold">Profilim</h2>
        <div class="flex items-center gap-4"><x-avatar :user="$user" size="size-14" /><input type="file" name="avatar" accept="image/*" class="text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2"></div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <x-field label="Ad, soyad" name="name"><input name="name" value="{{ old('name', $user->name) }}" required class="input"></x-field>
          <x-field label="Vəzifə" name="position"><input name="position" value="{{ old('position', $user->position) }}" class="input"></x-field>
          <x-field label="Telefon" name="phone"><input name="phone" value="{{ old('phone', $user->phone) }}" class="input"></x-field>
          <x-field label="E-poçt"><input value="{{ $user->email }}" disabled class="input bg-slate-50"></x-field>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="email_notifications" value="1" @checked($user->email_notifications) class="rounded border-slate-300 text-brand-600"> Bildirişləri mail ilə də al</label>
        <button class="btn-primary">Yadda saxla</button>
      </form>

      <form method="POST" action="{{ route('settings.password') }}" class="card space-y-4 p-6">
        @csrf @method('PUT')
        <h2 class="font-semibold">Şifrəni dəyiş</h2>
        <x-field label="Hazırkı şifrə" name="current_password"><input type="password" name="current_password" required class="input"></x-field>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <x-field label="Yeni şifrə" name="password"><input type="password" name="password" required class="input"></x-field>
          <x-field label="Təkrar"><input type="password" name="password_confirmation" required class="input"></x-field>
        </div>
        <button class="btn-primary">Şifrəni dəyiş</button>
      </form>
    </div>

    @if ($user->hasPerm('settings'))
    <div class="space-y-6">
      <div class="card p-6">
        <div class="flex items-start gap-3">
          <span class="inline-flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600"><x-icon name="cloud" /></span>
          <div class="flex-1"><h2 class="font-semibold">OneDrive fayl yaddaşı</h2>
            <p class="text-sm text-slate-500">Platformaya yüklənən bütün fayllar OneDrive-da saxlanılır. Platforma onları yalnız göstərir.</p></div>
        </div>
        <div class="mt-4 rounded-lg p-4 text-sm {{ $oneDrive['connected'] ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">
          @if ($oneDrive['connected'])
            <p class="font-medium">✓ Qoşulub: {{ $oneDrive['account'] }}</p>
            @if ($oneDrive['quota'])
              @php $used = $oneDrive['quota']['used'] ?? 0; $total = max(1, $oneDrive['quota']['total'] ?? 1); @endphp
              <div class="mt-3 h-2 overflow-hidden rounded-full bg-emerald-100"><div class="h-full bg-emerald-500" style="width: {{ min(100, round($used / $total * 100, 1)) }}%"></div></div>
              <p class="mt-1 text-xs">{{ Labels::bytes($used) }} / {{ Labels::bytes($total) }} istifadə olunub</p>
            @endif
          @elseif ($oneDrive['configured'])
            <p>Microsoft açarları yazılıb, amma hesab hələ qoşulmayıb. Aşağıdakı düymə ilə Microsoft hesabınızla daxil olun.</p>
          @else
            <p class="font-medium">Hələ qoşulmayıb — fayllar müvəqqəti olaraq serverdə saxlanılır.</p>
            <p class="mt-1">Qoşmaq üçün Microsoft App Registration məlumatları <code>.env</code> faylına yazılmalıdır (MICROSOFT_CLIENT_ID, MICROSOFT_CLIENT_SECRET, MICROSOFT_TENANT_ID).</p>
          @endif
        </div>
        <p class="mt-3 text-xs text-slate-500">Redirect URI: <code class="rounded bg-slate-100 px-1">{{ $oneDrive['redirect'] }}</code></p>
        <div class="mt-4 flex gap-2">
          @if ($oneDrive['configured'])
            <a href="{{ route('onedrive.connect') }}" class="btn-primary"><x-icon name="cloud" class="size-4" /> {{ $oneDrive['connected'] ? 'Başqa hesabla qoş' : 'OneDrive-ı qoş' }}</a>
          @endif
          @if ($oneDrive['connected'])
            <form method="POST" action="{{ route('onedrive.disconnect') }}" onsubmit="return confirm('OneDrive ayrılsın? Mövcud fayllar OneDrive-da qalır, amma platforma onlara çata bilməyəcək.')">@csrf<button class="btn-danger">Ayır</button></form>
          @endif
        </div>
      </div>

      <div class="card p-6">
        <div class="flex items-start gap-3">
          <span class="inline-flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><x-icon name="mail" /></span>
          <div class="flex-1"><h2 class="font-semibold">Mail bildirişləri</h2><p class="text-sm text-slate-500">Bildirişlər birtərəfli göndərilir (cavab yazmaq lazım deyil).</p></div>
        </div>
        <dl class="mt-4 space-y-1.5 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500">Göndərən</dt><dd class="font-medium">{{ $mail['from'] }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Server</dt><dd>{{ $mail['mailer'] === 'smtp' ? $mail['host'] : $mail['mailer'].' (test rejimi — mail getmir)' }}</dd></div>
        </dl>
        <form method="POST" action="{{ route('settings.testMail') }}" class="mt-4">@csrf<button class="btn-secondary"><x-icon name="send" class="size-4" /> Özümə test maili göndər</button></form>
      </div>

      <form method="POST" action="{{ route('settings.agency') }}" class="card space-y-4 p-6">
        @csrf @method('PUT')
        <h2 class="font-semibold">Agentlik və xatırlatmalar</h2>
        <x-field label="Agentliyin adı" name="agency_name"><input name="agency_name" value="{{ Setting::get('agency_name') }}" required class="input"></x-field>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="reminder_enabled" value="1" @checked(Setting::get('reminder_enabled') === '1') class="rounded border-slate-300 text-brand-600"> Müştəriyə avtomatik təsdiq xatırlatmaları göndər</label>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
          <x-field label="Hər neçə saatdan bir" name="reminder_interval_hours"><input type="number" min="1" max="24" name="reminder_interval_hours" value="{{ Setting::get('reminder_interval_hours') }}" class="input"></x-field>
          <x-field label="Gündə maksimum" name="reminder_max_per_day"><input type="number" min="1" max="24" name="reminder_max_per_day" value="{{ Setting::get('reminder_max_per_day') }}" class="input"></x-field>
          <x-field label="Paylaşımdan neçə gün əvvəl" name="reminder_days_before"><input type="number" min="0" max="14" name="reminder_days_before" value="{{ Setting::get('reminder_days_before') }}" class="input"></x-field>
          <x-field label="İş saatı başlanğıc" name="reminder_start_hour"><input type="number" min="0" max="23" name="reminder_start_hour" value="{{ Setting::get('reminder_start_hour') }}" class="input"></x-field>
          <x-field label="İş saatı son" name="reminder_end_hour"><input type="number" min="1" max="24" name="reminder_end_hour" value="{{ Setting::get('reminder_end_hour') }}" class="input"></x-field>
        </div>
        <p class="text-xs text-slate-500">Xatırlatmalar yalnız iş saatlarında gedir. Bir markanın bütün gözləyən paylaşımları bir mailə yığılır ki, müştəri spam hiss etməsin.</p>
        <button class="btn-primary">Yadda saxla</button>
      </form>
    </div>
    @endif
  </div>
</x-layouts.app>
