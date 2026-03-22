<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AdminInviteMail;
use App\Models\Admin;
use App\Models\AdminInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminSetupController extends Controller
{
    public function show(Request $request): View
    {
        abort_if(Admin::query()->exists(), 404);

        return view('auth.admin-setup', [
            'setupToken' => $request->query('token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(Admin::query()->exists(), 404);

        $validated = $request->validate([
            'setup_token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ]);

        abort_unless(
            hash_equals((string) config('status.setup_token'), $validated['setup_token']),
            403,
        );

        $invite = AdminInvite::query()->create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'token' => Str::random(48),
            'expires_at' => now()->addDay(),
        ]);

        Mail::to($invite->email)->queue(new AdminInviteMail($invite));

        return redirect()->route('admin.invites.show', $invite->token);
    }
}
