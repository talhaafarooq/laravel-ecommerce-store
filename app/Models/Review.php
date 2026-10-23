<?php

declare(strict_types = 1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                  $id
 * @property int                  $product_id
 * @property int|null             $user_id
 * @property int                  $rating
 * @property string|null          $title
 * @property string               $body
 * @property bool                 $is_approved
 * @property bool                 $is_verified_purchase
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property Product              $product
 * @property User|null            $user
 */
#[Fillable(['user_id', 'rating', 'title', 'body', 'is_approved', 'is_verified_purchase'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param Builder<self> $query
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('is_approved', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating'               => 'integer',
            'is_approved'          => 'boolean',
            'is_verified_purchase' => 'boolean',
        ];
    }
}
