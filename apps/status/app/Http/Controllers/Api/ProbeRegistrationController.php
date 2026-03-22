<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\RemoteIntegrations\PushProbeRegistrationHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProbeRegistrationController extends Controller
{
    public function __construct(
        protected readonly PushProbeRegistrationHandler $registrations,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->hasValidToken($request)) {
            return response()->json([
                'message' => $this->registrationErrorMessage($request),
            ], 401);
        }

        $integration = $this->registrations->handle($request->all());

        return response()->json([
            'message' => 'Probe registration synchronized.',
            'integration_id' => $integration->id,
            'service_id' => $integration->service_id,
        ]);
    }

    protected function hasValidToken(Request $request): bool
    {
        $expected = PlatformSetting::current()->probe_registration_token;
        $provided = $request->bearerToken();

        return filled($expected)
            && filled($provided)
            && hash_equals((string) $expected, (string) $provided);
    }

    protected function registrationErrorMessage(Request $request): string
    {
        $expected = PlatformSetting::current()->probe_registration_token;

        if (! filled($expected)) {
            return 'This monitor cannot accept probe registrations until a probe registration token is configured in Platform Settings.';
        }

        if (! filled($request->bearerToken())) {
            return 'Probe registration token is missing. Copy the current monitor token from Platform Settings and set it as STATUS_MONITOR_TOKEN in the remote app.';
        }

        return 'Probe registration token is invalid. Copy the current monitor token from Platform Settings and update STATUS_MONITOR_TOKEN in the remote app.';
    }
}
