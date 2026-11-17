<!DOCTYPE html>
<html lang="az">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ \App\Models\Setting::get('agency_name') }} Plan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-brand-50 font-sans text-slate-800 antialiased">
  <div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
      <div class="mb-8 flex flex-col items-center text-center">
        <span class="mb-3 inline-flex size-12 items-center justify-center rounded-xl bg-brand-600 text-lg font-bold text-white shadow-lg shadow-brand-600/30">{{ mb_substr(\App\Models\Setting::get('agency_name'), 0, 1) }}</span>
        <h1 class="text-xl font-semibold text-slate-900">{{ \App\Models\Setting::get('agency_name') }} Plan</h1>
        <p class="text-sm text-slate-500">Agentlik və müştəri əməkdaşlıq platforması</p>
      </div>
      <div class="card p-6">
        @if (session('status'))<div class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</div>@endif
        {{ $slot }}
      </div>
    </div>
  </div>
</body>
</html>
