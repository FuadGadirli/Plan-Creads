<x-layouts.guest>
  <p class="mb-4 text-sm text-slate-600">Hesabınız üçün yeni şifrə təyin edin.</p>
  <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <x-field label="E-poçt" name="email"><input id="email" type="email" name="email" value="{{ old('email', $email) }}" required class="input"></x-field>
    <x-field label="Yeni şifrə" name="password" hint="Ən azı 8 simvol"><input id="password" type="password" name="password" required class="input" autocomplete="new-password"></x-field>
    <x-field label="Şifrəni təkrarla"><input type="password" name="password_confirmation" required class="input" autocomplete="new-password"></x-field>
    <button class="btn-primary w-full">Şifrəni yadda saxla</button>
  </form>
</x-layouts.guest>
