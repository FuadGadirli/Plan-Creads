<x-layouts.guest>
  <p class="mb-4 text-sm text-slate-600">E-poçtunuzu yazın, şifrə bərpası linkini göndərək.</p>
  <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <x-field label="E-poçt" name="email"><input id="email" type="email" name="email" value="{{ old('email') }}" required class="input"></x-field>
    <button class="btn-primary w-full">Link göndər</button>
    <a href="{{ route('login') }}" class="block text-center text-sm text-slate-500 hover:text-slate-800">← Girişə qayıt</a>
  </form>
</x-layouts.guest>
