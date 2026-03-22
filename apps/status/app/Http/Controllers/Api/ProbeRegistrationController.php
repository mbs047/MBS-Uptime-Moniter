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
        abort_unless($this->hasValidToken($request), 401);

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
}
