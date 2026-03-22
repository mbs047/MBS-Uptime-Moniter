<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminInviteController extends Controller
{
    public function show(string $token): View
    {
        $invite = $this->resolveInvite($token);

        return view('auth.accept-admin-invite', [
            'invite' => $invite,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invite = $this->resolveInvite($token);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'min:10'],
        ]);

        $admin = Admin::query()->updateOrCreate(
            ['email' => $invite->email],
            [
                'name' => $validated['name'],
                'password' => $validated['password'],
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $invite->forceFill([
            'accepted_at' => now(),
        ])->save();

        Auth::guard('admin')->login($admin);

        return redirect('/admin');
    }

    protected function resolveInvite(string $token): AdminInvite
    {
        $invite = AdminInvite::query()
            ->where('token', $token)
            ->firstOrFail();

        abort_if($invite->accepted_at, 404);
        abort_if($invite->expires_at && $invite->expires_at->isPast(), 404);

        return $invite;
    }
}
