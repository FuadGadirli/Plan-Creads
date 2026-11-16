<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Brand;
use App\Models\Client;
use App\Models\ClientEvent;
use App\Models\User;
use App\Services\FileStore;
use App\Support\Labels;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $clients = Client::with(['owner', 'brands'])
            ->withMax('events', 'happened_at')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%{$v}%")->orWhere('contact_person', 'like', "%{$v}%")))
            ->orderBy('name')->get();
        $counts = Client::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('clients.index', compact('clients', 'status', 'counts'));
    }

    public function create()
    {
        return view('clients.form', ['client' => new Client(['status' => 'lead', 'first_contact_at' => today()]), 'staff' => User::staff()->active()->orderBy('name')->get()]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'industry' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'contact_position' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Labels::CLIENT_STATUSES_CRM))],
            'first_contact_at' => ['nullable', 'date'],
            'started_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ]);
    }

    public function store(Request $request)
    {
        $client = Client::create($this->validated($request));
        $client->events()->create([
            'user_id' => $request->user()->id, 'type' => 'note', 'title' => 'Müştəri kartı yaradıldı',
            'happened_at' => $client->first_contact_at ?? now(),
        ]);
        Activity::log("Yeni müştəri əlavə etdi: {$client->name}", $client);

        return redirect()->route('clients.show', $client)->with('success', 'Müştəri əlavə edildi.');
    }

    public function show(Client $client)
    {
        $client->load(['events.user', 'brands', 'owner', 'attachments.uploader', 'users']);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('clients.form', ['client' => $client, 'staff' => User::staff()->active()->orderBy('name')->get()]);
    }

    public function update(Request $request, Client $client)
    {
        $data = $this->validated($request);
        $old = $client->status;
        $client->update($data);
        if ($old !== $client->status) {
            $client->events()->create([
                'user_id' => $request->user()->id, 'type' => 'note',
                'title' => 'Status dəyişdi: '.Labels::label(Labels::CLIENT_STATUSES_CRM, $client->status), 'happened_at' => now(),
            ]);
        }

        return redirect()->route('clients.show', $client)->with('success', 'Məlumatlar yeniləndi.');
    }

    public function destroy(Client $client)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        foreach ($client->attachments as $a) {
            FileStore::delete($a);
        }
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Müştəri silindi.');
    }

    public function event(Request $request, Client $client)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Labels::CLIENT_EVENT_TYPES))],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'happened_at' => ['required', 'date'],
        ]);
        $client->events()->create([...$data, 'user_id' => $request->user()->id]);

        return back()->with('success', 'Qeyd əlavə edildi.');
    }

    public function destroyEvent(Client $client, ClientEvent $event)
    {
        abort_unless($event->client_id === $client->id, 404);
        $event->delete();

        return back()->with('success', 'Qeyd silindi.');
    }

    public function files(Request $request, Client $client)
    {
        $data = $request->validate(['attachment_ids' => ['required', 'array']]);
        FileStore::claim($data['attachment_ids'], $request->user(), $client, ['client_visible' => false]);

        return back()->with('success', 'Sənəd yükləndi.');
    }

    /** Aktiv müştəridən bir kliklə marka yaradır */
    public function makeBrand(Client $client)
    {
        $brand = Brand::create(['name' => $client->name, 'client_id' => $client->id, 'color' => '#6366f1', 'description' => $client->notes]);
        foreach (['Brendbuk', 'Kataloq', 'Loqolar', 'Foto', 'Video'] as $name) {
            $brand->folders()->create(['name' => $name]);
        }
        if ($client->status !== 'active') {
            $client->update(['status' => 'active', 'started_at' => $client->started_at ?? today()]);
        }

        return redirect()->route('brands.edit', $brand)->with('success', 'Marka yaradıldı. Loqo və rəngi əlavə edin.');
    }
}
