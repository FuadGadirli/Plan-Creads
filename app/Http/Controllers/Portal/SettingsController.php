<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class SettingsController extends PortalController
{
    public function index(Request $request)
    {
        $brand = $this->brand($request);

        return view('portal.settings', ['user' => $request->user(), 'brand' => $brand]);
    }

    public function profile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'position' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);
        $request->user()->fill($data)->forceFill(['email_notifications' => $request->boolean('email_notifications')])->save();

        return back()->with('success', 'Məlumatlarınız yeniləndi.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);
        if (! Hash::check($request->input('current_password'), $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Hazırkı şifrə yanlışdır.']);
        }
        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('success', 'Şifrə dəyişdirildi.');
    }
}
