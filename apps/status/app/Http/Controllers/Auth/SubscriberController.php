<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SubscriberConfirmationMail;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscriberController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
        ]);

        $subscriber = Subscriber::query()->firstOrNew([
            'email' => Str::lower($validated['email']),
        ]);

        $subscriber->verification_token = Str::random(48);
        $subscriber->unsubscribe_token = $subscriber->unsubscribe_token ?: Str::random(48);
        $subscriber->unsubscribed_at = null;
        $subscriber->last_confirmation_sent_at = now();
        $subscriber->save();

        Mail::to($subscriber->email)->queue(new SubscriberConfirmationMail($subscriber));

        return response()->json([
            'message' => 'If this address is valid, a confirmation email has been sent.',
        ]);
    }

    public function confirm(string $token): RedirectResponse
    {
        $subscriber = Subscriber::query()
            ->where('verification_token', $token)
            ->firstOrFail();

        $subscriber->forceFill([
            'verified_at' => now(),
            'verification_token' => null,
        ])->save();

        return redirect('/')->with('status', 'Subscription confirmed.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = Subscriber::query()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        $subscriber->forceFill([
            'unsubscribed_at' => now(),
        ])->save();

        return redirect('/')->with('status', 'You have been unsubscribed.');
    }
}
