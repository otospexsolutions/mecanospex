<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Device model for tracking user devices.
 *
 * @property string $id UUID of the device
 * @property string $user_id UUID of the user
 * @property string $name Device name (e.g., "iPhone 15", "Chrome on MacOS")
 * @property string $type Device type (mobile, desktop, tablet, pos)
 * @property string|null $device_id Unique device identifier (for push notifications)
 * @property string|null $push_token Push notification token
 * @property string|null $platform OS platform (ios, android, windows, macos, linux, web)
 * @property string|null $platform_version OS version
 * @property string|null $app_version Application version
 * @property bool $is_trusted Whether this device is trusted (biometric enabled)
 * @property bool $is_active Whether this device is currently active
 * @property Carbon|null $last_used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Device extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'devices';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'device_id',
        'push_token',
        'platform',
        'platform_version',
        'app_version',
        'is_trusted',
        'is_active',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_trusted' => 'boolean',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the device.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark this device as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Trust this device (enable biometric auth).
     */
    public function trust(): void
    {
        $this->update(['is_trusted' => true]);
    }

    /**
     * Revoke trust from this device.
     */
    public function revokeTrust(): void
    {
        $this->update(['is_trusted' => false]);
    }
}
