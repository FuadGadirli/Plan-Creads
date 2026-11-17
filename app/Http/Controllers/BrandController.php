<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Brand;
use App\Models\Client;
use App\Models\ContentPlan;
use App\Models\Post;
use App\Models\User;
use App\Notifications\InviteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $showInactive = $request->boolean('inactive');
        $brands = Brand::query()
            ->where('is_active', ! $showInactive)
            ->withCount([
                'tasks as open_tasks' => fn ($q) => $q->where('status', '!=', 'done'),
                'tasks as overdue_tasks' => fn ($q) => $q->where('status', '!=', 'done')->whereDate('due_date', '<', today()),
                'posts as month_posts' => fn ($q) => $q->whereYear('publish_date', now()->year)->whereMonth('publish_date', now()->month),
                'posts as awaiting_posts' => fn ($q) => $q->whereIn('status', ['pending', 'sent', 'client_changes']),
            ])
            ->with('client')
            ->orderBy('name')->get();

        return view('brands.index', compact('brands', 'showInactive'));
    }

    public function create(Request $request)
    {
        $brand = new Brand(['color' => '#6366f1', 'client_id' => $request->query('client')]);

        return view('brands.form', ['brand' => $brand, 'clients' => Client::orderBy('name')->get()]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:5000'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $contact = $request->validate([
            'contact_name' => ['nullable', 'string', 'max:120', 'required_with:contact_email'],
            'contact_email' => ['nullable', 'email', 'max:150'],
        ]);
        if (! empty($contact['contact_email'])) {
            $this->assertClientEmail($contact['contact_email']);
        }
        $brand = new Brand(collect($data)->except('logo')->all());
        $brand->is_active = true;
        if ($request->hasFile('logo')) {
            $brand->logo_path = $request->file('logo')->store('logos', 'public');
        }
        $brand->save();
        foreach (['Brendbuk', 'Kataloq', 'Loqolar', 'Foto', 'Video'] as $name) {
            $brand->folders()->create(['name' => $name]);
        }
        Activity::log("“{$brand->name}” markasını yaratdı", $brand, $brand->id);

        $msg = 'Marka yaradıldı.';
        if (! empty($contact['contact_email'])) {
            $this->attachContact($brand, $contact['contact_name'], $contact['contact_email'], $request->user());
            $msg .= ' Müştəriyə ('.$contact['contact_email'].') dəvət maili göndərildi.';
        }

        return redirect()->route('brands.show', $brand)->with('success', $msg);
    }

    public function show(Brand $brand)
    {
        $stats = [
            'open_tasks' => $brand->tasks()->where('status', '!=', 'done')->count(),
            'overdue' => $brand->tasks()->where('status', '!=', 'done')->whereDate('due_date', '<', today())->count(),
            'files' => $brand->libraryFiles()->count(),
            'month_posts' => $brand->posts()->whereYear('publish_date', now()->year)->whereMonth('publish_date', now()->month)->count(),
            'awaiting_client' => $brand->posts()->where('status', 'sent')->count(),
        ];
        $currentPlan = ContentPlan::where('brand_id', $brand->id)->where('year', now()->year)->where('month', now()->month)->first();
        $nextPosts = Post::where('brand_id', $brand->id)->where('publish_date', '>=', today())->orderBy('publish_date')->limit(5)->get();
        $activities = Activity::with('user')->where('brand_id', $brand->id)->latest()->limit(10)->get();
        $brand->load('client', 'clientUsers');

        return view('brands.show', compact('brand', 'stats', 'currentPlan', 'nextPosts', 'activities'));
    }

    public function edit(Brand $brand)
    {
        $brand->load('clientUsers');

        return view('brands.form', ['brand' => $brand, 'clients' => Client::orderBy('name')->get()]);
    }

    /** E-poçt agentlik işçisinə məxsusdursa, müştəri kimi əlavə etmək olmaz */
    private function assertClientEmail(string $email): void
    {
        $existing = User::where('email', $email)->first();
        if ($existing && ! $existing->isClient()) {
            throw ValidationException::withMessages(['contact_email' => 'Bu e-poçt agentlik işçisinə aiddir, müştəri hesabı kimi istifadə oluna bilməz.']);
        }
    }

    /**
     * Müştəri hesabını yaradır (və ya mövcudunu tapır), markaya bağlayır, dəvət göndərir.
     * Marka hələ CRM-də müştəriyə bağlı deyilsə, avtomatik müştəri kartı da açılır.
     */
    private function attachContact(Brand $brand, string $name, string $email, User $inviter): User
    {
        if (! $brand->client_id) {
            $client = Client::create([
                'name' => $brand->name, 'contact_person' => $name, 'email' => $email,
                'status' => 'active', 'started_at' => today(), 'first_contact_at' => today(), 'owner_id' => $inviter->id,
            ]);
            $brand->update(['client_id' => $client->id]);
        }

        $user = User::where('email', $email)->first();
        $isNew = ! $user;
        if ($isNew) {
            $user = User::create([
                'name' => $name, 'email' => $email, 'password' => Str::random(40),
                'role' => 'client', 'client_id' => $brand->client_id, 'is_active' => true, 'email_notifications' => true,
            ]);
        }
        $user->brands()->syncWithoutDetaching([$brand->id]);

        if ($isNew) {
            $user->notify(new InviteNotification(Password::broker()->createToken($user), $inviter->name));
        }
        Activity::log("{$brand->name} markasına müştəri hesabı əlavə etdi: {$email}", $brand, $brand->id);

        return $user;
    }

    public function addContact(Request $request, Brand $brand)
    {
        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:150'],
        ]);
        $this->assertClientEmail($data['contact_email']);
        $this->attachContact($brand, $data['contact_name'], $data['contact_email'], $request->user());

        return back()->with('success', $data['contact_email'].' əlavə edildi və dəvət maili göndərildi.');
    }

    public function removeContact(Brand $brand, User $user)
    {
        abort_unless($user->isClient(), 404);
        $user->brands()->detach($brand->id);
        if (! $user->brands()->exists()) {
            $user->update(['is_active' => false]);
        }

        return back()->with('success', $user->email.' bu markadan çıxarıldı.');
    }

    public function inviteContact(Request $request, Brand $brand, User $user)
    {
        abort_unless($user->isClient() && $user->brands()->whereKey($brand->id)->exists(), 404);
        $user->update(['is_active' => true]);
        $user->notify(new InviteNotification(Password::broker()->createToken($user), $request->user()->name));

        return back()->with('success', 'Dəvət maili yenidən göndərildi: '.$user->email);
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validated($request);
        $brand->fill(collect($data)->except('logo')->all());
        $brand->is_active = $request->boolean('is_active');
        if ($request->hasFile('logo')) {
            $brand->logo_path = $request->file('logo')->store('logos', 'public');
        }
        $brand->save();

        return redirect()->route('brands.show', $brand)->with('success', 'Marka yeniləndi.');
    }
}
