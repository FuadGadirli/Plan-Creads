@php use App\Support\Labels; @endphp
<x-layouts.app :title="$client->name">
  <x-page-header :title="$client->name" :subtitle="$client->industry">
    <x-slot:breadcrumb><a href="{{ route('clients.index') }}" class="hover:underline">Əməkdaşlıq</a></x-slot:breadcrumb>
    <x-slot:actions>
      <x-status :map="Labels::CLIENT_STATUSES_CRM" :value="$client->status" class="!text-sm" />
      @if ($client->brands->isEmpty() && auth()->user()->hasPerm('brands.manage'))
        <form method="POST" action="{{ route('clients.brand', $client) }}">@csrf<button class="btn-secondary"><x-icon name="brands" class="size-4" /> Marka yarat</button></form>
      @endif
      <a href="{{ route('clients.edit', $client) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Redaktə</a>
    </x-slot:actions>
  </x-page-header>

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6">
      <div class="card p-5">
        <h3 class="mb-3 font-semibold">Əlaqə məlumatları</h3>
        <dl class="space-y-2.5 text-sm">
          <div class="flex gap-2"><x-icon name="user" class="size-4 text-slate-400" /><span>{{ $client->contact_person ?: '—' }}{{ $client->contact_position ? ' · '.$client->contact_position : '' }}</span></div>
          <div class="flex gap-2"><x-icon name="mail" class="size-4 text-slate-400" />@if($client->email)<a href="mailto:{{ $client->email }}" class="text-brand-600 hover:underline">{{ $client->email }}</a>@else — @endif</div>
          <div class="flex gap-2"><x-icon name="phone" class="size-4 text-slate-400" />{{ $client->phone ?: '—' }}</div>
          <div class="flex gap-2"><x-icon name="globe" class="size-4 text-slate-400" />@if($client->website)<a href="{{ str_starts_with($client->website, 'http') ? $client->website : 'https://'.$client->website }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline">{{ $client->website }}</a>@else — @endif</div>
          @if ($client->address)<div class="flex gap-2"><x-icon name="home" class="size-4 text-slate-400" />{{ $client->address }}</div>@endif
        </dl>
        <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500">İlk əlaqə</dt><dd>{{ Labels::date($client->first_contact_at) }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Əməkdaşlıq başlayıb</dt><dd>{{ Labels::date($client->started_at) }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Agentlikdə məsul</dt><dd>{{ $client->owner?->name ?? '—' }}</dd></div>
          <div class="flex justify-between gap-2"><dt class="text-slate-500">Markalar</dt><dd class="text-right">@forelse ($client->brands as $b)<a href="{{ route('brands.show', $b) }}" class="text-brand-600 hover:underline">{{ $b->name }}</a>@if(! $loop->last), @endif @empty — @endforelse</dd></div>
        </dl>
        @if ($client->notes)<div class="prose-plain mt-4 border-t border-slate-100 pt-4">{{ $client->notes }}</div>@endif
      </div>
      <div class="card p-5">
        <h3 class="mb-3 font-semibold">Sənədlər <span class="text-slate-400">({{ $client->attachments->count() }})</span></h3>
        <ul class="mb-3 space-y-1.5">
          @foreach ($client->attachments as $f)
            <li class="flex items-center gap-2 text-sm"><x-icon name="doc" class="size-4 text-slate-400" /><a href="{{ $f->previewUrl() }}" class="min-w-0 flex-1 truncate hover:text-brand-700">{{ $f->original_name }}</a><span class="text-xs text-slate-400">{{ $f->sizeLabel() }}</span></li>
          @endforeach
        </ul>
        <form method="POST" action="{{ route('clients.files', $client) }}">@csrf<x-uploader scope="crm" :autosubmit="true" compact label="Müqavilə, təklif və s." /></form>
      </div>
    </div>

    <div class="space-y-6 lg:col-span-2">
      <div class="card p-5">
        <h3 class="mb-4 font-semibold">Yeni toxunuş əlavə et</h3>
        <form method="POST" action="{{ route('clients.events.store', $client) }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
          @csrf
          <select name="type" class="input">@foreach (Labels::CLIENT_EVENT_TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
          <input name="title" required class="input sm:col-span-2" placeholder="Məs: Təqdimat görüşü">
          <input type="datetime-local" name="happened_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="input">
          <textarea name="description" rows="2" class="input sm:col-span-3" placeholder="Detallar, nəticə, növbəti addım…"></textarea>
          <button class="btn-primary">Əlavə et</button>
        </form>
      </div>
      <div class="card p-5">
        <h3 class="mb-4 font-semibold">Əməkdaşlıq tarixçəsi</h3>
        @php $icons = ['meeting' => 'team', 'call' => 'phone', 'email' => 'mail', 'contract' => 'doc', 'note' => 'pencil']; @endphp
        <ol class="relative space-y-5 border-l border-slate-200 pl-6">
          @forelse ($client->events as $e)
            <li class="relative">
              <span class="absolute -left-[37px] inline-flex size-7 items-center justify-center rounded-full bg-white text-brand-600 ring-1 ring-slate-200"><x-icon :name="$icons[$e->type] ?? 'pencil'" class="size-3.5" /></span>
              <div class="flex flex-wrap items-center gap-2">
                <x-badge>{{ Labels::CLIENT_EVENT_TYPES[$e->type] ?? $e->type }}</x-badge>
                <span class="font-medium text-slate-900">{{ $e->title }}</span>
                <span class="text-xs text-slate-400">{{ Labels::date($e->happened_at, true) }} · {{ $e->user?->name }}</span>
                <form method="POST" action="{{ route('clients.events.destroy', [$client, $e]) }}" class="ml-auto" onsubmit="return confirm('Silinsin?')">@csrf @method('DELETE')<button class="text-slate-300 hover:text-rose-600"><x-icon name="x" class="size-4" /></button></form>
              </div>
              @if ($e->description)<p class="prose-plain mt-1">{{ $e->description }}</p>@endif
            </li>
          @empty
            <li class="text-sm text-slate-500">Hələ qeyd yoxdur.</li>
          @endforelse
        </ol>
      </div>
    </div>
  </div>
</x-layouts.app>
