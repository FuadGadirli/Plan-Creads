@php use App\Support\Labels; $editing = $task->exists; @endphp
<x-layouts.app :title="$editing ? 'Tapşırığı redaktə et' : 'Yeni tapşırıq'">
  <x-page-header :title="$editing ? 'Tapşırığı redaktə et' : 'Yeni tapşırıq ver'">
    <x-slot:breadcrumb><a href="{{ route('tasks.index') }}" class="hover:underline">Tapşırıqlar</a></x-slot:breadcrumb>
  </x-page-header>

  <form method="POST" action="{{ $editing ? route('tasks.update', $task) : route('tasks.store') }}" class="grid grid-cols-1 gap-6 lg:grid-cols-3"
        x-data="{ brand: '{{ old('brand_id', $task->brand_id) }}' }">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="card space-y-5 p-6 lg:col-span-2">
      <x-field label="Başlıq" name="title">
        <input id="title" name="title" value="{{ old('title', $task->title) }}" required class="input text-base" placeholder="Məs: Noyabr ayı üçün 12 post dizaynı">
      </x-field>
      <x-field label="Təsvir" name="description">
        <textarea id="description" name="description" rows="7" class="input" placeholder="Detallar, tələblər, linklər…">{{ old('description', $task->description) }}</textarea>
      </x-field>
      <x-field label="Fayllar" hint="İstənilən ölçüdə fayl yükləyə bilərsiniz (video da daxil).">
        <x-uploader scope="tasks" :brand="$task->brand" brand-select="select[name=brand_id]" />
      </x-field>
    </div>

    <div class="space-y-6">
      <div class="card space-y-4 p-6">
        <x-field label="Marka" name="brand_id">
          <select name="brand_id" x-model="brand" class="input">
            <option value="">Ümumi (markaya aid deyil)</option>
            @foreach ($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $task->brand_id) == $b->id)>{{ $b->name }}</option>@endforeach
          </select>
        </x-field>
        <x-field label="Deadline" name="due_date">
          <input type="date" name="due_date" value="{{ old('due_date', $task->due_date?->toDateString()) }}" class="input">
        </x-field>
        <div class="grid grid-cols-2 gap-3">
          <x-field label="Status" name="status">
            <select name="status" class="input">@foreach (Labels::TASK_STATUSES as $k => [$l])<option value="{{ $k }}" @selected(old('status', $task->status) === $k)>{{ $l }}</option>@endforeach</select>
          </x-field>
          <x-field label="Prioritet" name="priority">
            <select name="priority" class="input">@foreach (Labels::PRIORITIES as $k => [$l])<option value="{{ $k }}" @selected(old('priority', $task->priority) === $k)>{{ $l }}</option>@endforeach</select>
          </x-field>
        </div>
      </div>

      <div class="card space-y-4 p-6" x-data="{ r: @js(array_map('intval', old('responsible', $assigned['responsible']))), s: @js(array_map('intval', old('supporter', $assigned['supporter']))), w: @js(array_map('intval', old('watcher', $assigned['watcher']))) }">
        <p class="text-sm text-slate-500">Seçilən şəxslər tapşırığı öz panelində görəcək və mail ilə bildiriş alacaq.</p>
        @foreach (['responsible' => ['Məsul şəxs(lər)', 'r'], 'supporter' => ['Dəstəkçi şəxs(lər)', 's'], 'watcher' => ['Tag et (məlumat üçün)', 'w']] as $role => [$label, $var])
          <div>
            <p class="label">{{ $label }}</p>
            <div class="flex flex-wrap gap-1.5">
              @foreach ($staff as $s)
                <label class="cursor-pointer">
                  <input type="checkbox" name="{{ $role }}[]" value="{{ $s->id }}" x-model.number="{{ $var }}" class="peer sr-only">
                  <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-slate-200 text-slate-600 peer-checked:bg-brand-600 peer-checked:text-white peer-checked:ring-brand-600">{{ $s->name }}</span>
                </label>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>

      <div class="flex gap-2">
        <button class="btn-primary flex-1">{{ $editing ? 'Yadda saxla' : 'Tapşırığı ver' }}</button>
        <a href="{{ $editing ? route('tasks.show', $task) : route('tasks.index') }}" class="btn-secondary">Ləğv et</a>
      </div>
    </div>
  </form>
</x-layouts.app>
