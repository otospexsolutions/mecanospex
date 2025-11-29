<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Controllers;

use App\Modules\Identity\Application\DTOs\AuthUserData;
use App\Modules\Identity\Application\DTOs\LoginResponseData;
use App\Modules\Identity\Domain\Device;
use App\Modules\Identity\Domain\User;
use App\Modules\Identity\Presentation\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate user and return token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact support.'],
            ]);
        }

        // Record login
        $user->recordLogin($request->ip() ?? 'unknown');

        // Handle device registration
        $device = $this->handleDevice($user, $validated);

        // Create token with device name
        $tokenName = $validated['device_name'] ?? 'api-token';
        $token = $user->createToken($tokenName, ['*']);

        $response = new LoginResponseData(
            user: AuthUserData::fromUser($user),
            token: $token->plainTextToken,
            tokenType: 'Bearer',
            deviceId: $device?->device_id,
        );

        return response()->json([
            'data' => $response,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Revoke current token
        $user->currentAccessToken()->delete();

        return response()->json([
            'data' => ['message' => 'Successfully logged out'],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * Log the user out from all devices.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Revoke all tokens
        $user->tokens()->delete();

        // Deactivate all devices
        $user->devices()->update(['is_active' => false]);

        return response()->json([
            'data' => ['message' => 'Successfully logged out from all devices'],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => AuthUserData::fromUser($user),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }

    /**
     * Handle device registration/update during login.
     *
     * @param  array<string, mixed>  $validated
     */
    private function handleDevice(User $user, array $validated): ?Device
    {
        $deviceId = $validated['device_id'] ?? null;
        $deviceName = $validated['device_name'] ?? null;

        if ($deviceId === null && $deviceName === null) {
            return null;
        }

        $deviceData = [
            'name' => $deviceName ?? 'Unknown Device',
            'type' => $this->detectDeviceType($validated['platform'] ?? null),
            'platform' => $validated['platform'] ?? null,
            'platform_version' => $validated['platform_version'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'last_used_at' => now(),
            'is_active' => true,
        ];

        if ($deviceId !== null) {
            $device = $user->devices()->where('device_id', $deviceId)->first();
            if ($device !== null) {
                $device->update($deviceData);

                return $device;
            }

            $deviceData['device_id'] = $deviceId;
        }

        return $user->devices()->create($deviceData);
    }

    /**
     * Detect device type from platform.
     */
    private function detectDeviceType(?string $platform): string
    {
        return match ($platform) {
            'ios', 'android' => 'mobile',
            'windows', 'macos', 'linux' => 'desktop',
            'web' => 'desktop',
            default => 'desktop',
        };
    }
}
