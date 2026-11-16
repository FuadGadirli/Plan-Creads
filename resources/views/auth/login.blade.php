<x-layouts.guest>
  <form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <x-field label="E-poçt" name="email">
      <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input" autocomplete="username">
    </x-field>
    <x-field label="Şifrə" name="password">
      <input id="password" type="password" name="password" required class="input" autocomplete="current-password">
    </x-field>
    <div class="flex items-center justify-between text-sm">
      <label class="flex items-center gap-2 text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600"> Məni xatırla</label>
      <a href="{{ route('password.request') }}" class="text-brand-600 hover:underline">Şifrəni unutmusan?</a>
    </div>
    <button class="btn-primary w-full">Daxil ol</button>
  </form>
</x-layouts.guest>
