<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\OneDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $quota = OneDrive::connected() ? OneDrive::quota() : null;

        return view('settings.index', [
            'user' => $request->user(),
            'oneDrive' => [
                'configured' => OneDrive::configured(),
                'connected' => OneDrive::connected(),
                'account' => OneDrive::accountLabel(),
                'quota' => $quota,
                'redirect' => OneDrive::configured() ? OneDrive::redirectUri() : route('onedrive.callback'),
            ],
            'mail' => [
                'mailer' => config('mail.default'),
                'from' => config('mail.from.address'),
                'host' => config('mail.mailers.smtp.host'),
            ],
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'position' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
        $user->fill(collect($data)->except('avatar')->all());
        $user->email_notifications = $request->boolean('email_notifications');
        if ($request->hasFile('avatar')) {
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }
        $user->save();

        return back()->with('success', 'Profil yeniləndi.');
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

    public function agency(Request $request)
    {
        $data = $request->validate([
            'agency_name' => ['required', 'string', 'max:100'],
            'reminder_interval_hours' => ['required', 'integer', 'min:1', 'max:24'],
            'reminder_start_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'reminder_end_hour' => ['required', 'integer', 'min:1', 'max:24'],
            'reminder_max_per_day' => ['required', 'integer', 'min:1', 'max:24'],
            'reminder_days_before' => ['required', 'integer', 'min:0', 'max:14'],
        ]);
        foreach ($data as $k => $v) {
            Setting::put($k, (string) $v);
        }
        Setting::put('reminder_enabled', $request->boolean('reminder_enabled') ? '1' : '0');

        return back()->with('success', 'Ayarlar yadda saxlanıldı.');
    }

    public function testMail(Request $request)
    {
        try {
            Mail::raw('Bu, '.Setting::get('agency_name').' Plan platformasından test mailidir. Mail ayarları düzgün işləyir.', function ($m) use ($request) {
                $m->to($request->user()->email)->subject('Test maili — '.Setting::get('agency_name').' Plan');
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['mail' => 'Mail göndərilmədi: '.$e->getMessage()]);
        }

        return back()->with('success', 'Test maili '.$request->user()->email.' ünvanına göndərildi.');
    }

    public function oneDriveConnect(Request $request)
    {
        if (! OneDrive::configured()) {
            return back()->withErrors(['onedrive' => 'Əvvəlcə .env faylında MICROSOFT_CLIENT_ID və MICROSOFT_CLIENT_SECRET yazılmalıdır.']);
        }
        $state = Str::random(40);
        $request->session()->put('onedrive_state', $state);

        return redirect()->away(OneDrive::authorizeUrl($state));
    }

    public function oneDriveCallback(Request $request)
    {
        if ($request->query('error')) {
            return redirect()->route('settings.index')->withErrors(['onedrive' => 'Microsoft: '.$request->query('error_description', $request->query('error'))]);
        }
        abort_unless($request->query('state') && $request->query('state') === $request->session()->pull('onedrive_state'), 419, 'Sessiya vaxtı bitib, yenidən yoxlayın.');

        try {
            OneDrive::handleCallback((string) $request->query('code'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('settings.index')->withErrors(['onedrive' => $e->getMessage()]);
        }

        return redirect()->route('settings.index')->with('success', 'OneDrive uğurla qoşuldu. Yeni fayllar artıq OneDrive-a yüklənəcək.');
    }

    public function oneDriveDisconnect()
    {
        OneDrive::disconnect();

        return back()->with('success', 'OneDrive ayrıldı. Yeni fayllar müvəqqəti olaraq serverdə saxlanacaq.');
    }
}
