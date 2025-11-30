<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $type
 * @property int $year
 * @property int $last_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DocumentSequence extends Model
{
    use HasUuids;

    /**
     * @var string
     */
    protected $table = 'document_sequences';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'type',
        'year',
        'last_number',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
