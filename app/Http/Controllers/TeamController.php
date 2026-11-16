<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\User;
use App\Notifications\InviteNotification;
use App\Support\Labels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'clients' ? 'clients' : 'staff';
        $users = User::query()
            ->when($tab === 'staff', fn ($q) => $q->staff(), fn ($q) => $q->where('role', 'client')->with(['brands', 'client']))
            ->withCount(['tasks as open_tasks' => fn ($q) => $q->where('status', '!=', 'done')])
            ->orderByDesc('is_active')->orderBy('name')->get();

        return view('team.index', compact('users', 'tab'));
    }

    public function create(Request $request)
    {
        $role = in_array($request->query('role'), ['admin', 'employee', 'client']) ? $request->query('role') : 'employee';

        return view('team.form', $this->formData(new User([
            'role' => $role,
            'permissions' => ['tasks.create', 'brands', 'brands.files', 'brands.social', 'brands.content'],
            'is_active' => true,
            'email_notifications' => true,
        ])));
    }

    private function formData(User $user): array
    {
        return [
            'user' => $user,
            'brands' => Brand::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
            'userBrands' => $user->exists ? $user->brands()->pluck('brands.id')->all() : [],
        ];
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(array_keys(Labels::ROLES))],
            'position' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', PasswordRule::min(8)],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(array_keys(Labels::PERMISSIONS))],
            'brand_ids' => ['array'],
            'brand_ids.*' => ['exists:brands,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        abort_if($data['role'] === 'admin' && ! $request->user()->isAdmin(), 403, 'Rəhbərlik hesabını yalnız rəhbərlik yarada bilər.');

        $user = User::create([
            ...collect($data)->only(['name', 'email', 'role', 'position', 'phone'])->all(),
            'password' => $data['password'] ?? Str::random(40),
            'permissions' => $data['role'] === 'employee' ? ($data['permissions'] ?? []) : null,
            'client_id' => $data['role'] === 'client' ? ($data['client_id'] ?? null) : null,
            'is_active' => true,
            'email_notifications' => true,
        ]);
        if ($user->isClient()) {
            $user->brands()->sync($data['brand_ids'] ?? []);
        }

        if ($request->boolean('send_invite') || empty($data['password'])) {
            $token = Password::broker()->createToken($user);
            $user->notify(new InviteNotification($token, $request->user()->name));
        }

        return redirect()->route('team.index', ['tab' => $user->isClient() ? 'clients' : 'staff'])
            ->with('success', $user->name.' əlavə edildi.'.(empty($data['password']) ? ' Şifrə təyin etmə linki mailinə göndərildi.' : ''));
    }

    public function edit(User $user)
    {
        return view('team.form', $this->formData($user));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        $me = $request->user();
        abort_if(($user->isAdmin() || $data['role'] === 'admin') && ! $me->isAdmin(), 403);
        abort_if($user->id === $me->id && ($data['role'] !== $user->role || ! $request->boolean('is_active')), 422, 'Öz rolunuzu dəyişə və ya özünüzü deaktiv edə bilməzsiniz.');

        $user->fill([
            ...collect($data)->only(['name', 'email', 'role', 'position', 'phone'])->all(),
            'permissions' => $data['role'] === 'employee' ? ($data['permissions'] ?? []) : null,
            'client_id' => $data['role'] === 'client' ? ($data['client_id'] ?? null) : null,
            'is_active' => $request->boolean('is_active'),
        ]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        $user->brands()->sync($user->isClient() ? ($data['brand_ids'] ?? []) : []);

        if ($request->boolean('send_invite')) {
            $user->notify(new InviteNotification(Password::broker()->createToken($user), $me->name));
        }

        return redirect()->route('team.index', ['tab' => $user->isClient() ? 'clients' : 'staff'])->with('success', 'Yadda saxlanıldı.');
    }
}
