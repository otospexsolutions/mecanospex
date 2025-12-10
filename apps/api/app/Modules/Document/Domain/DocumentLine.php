<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain;

use App\Modules\Product\Domain\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $document_id
 * @property string|null $product_id
 * @property int $line_number
 * @property string $description
 * @property numeric-string $quantity
 * @property numeric-string $unit_price
 * @property numeric-string|null $discount_percent
 * @property numeric-string|null $discount_amount
 * @property numeric-string|null $tax_rate
 * @property numeric-string $line_total
 * @property numeric-string $allocated_costs
 * @property numeric-string|null $landed_unit_cost
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Document $document
 * @property-read Product|null $product
 */
class DocumentLine extends Model
{
    use HasUuids;

    /**
     * @var string
     */
    protected $table = 'document_lines';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_id',
        'product_id',
        'line_number',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_rate',
        'line_total',
        'allocated_costs',
        'landed_unit_cost',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate the line total
     */
    public function calculateTotal(): string
    {
        $subtotal = bcmul($this->quantity, $this->unit_price, 2);

        // Apply discount if any
        if ($this->discount_percent !== null && $this->discount_percent !== '0.00') {
            $discount = bcmul($subtotal, bcdiv($this->discount_percent, '100', 4), 2);
            $subtotal = bcsub($subtotal, $discount, 2);
        } elseif ($this->discount_amount !== null && $this->discount_amount !== '0.00') {
            $subtotal = bcsub($subtotal, $this->discount_amount, 2);
        }

        return $subtotal;
    }
}
