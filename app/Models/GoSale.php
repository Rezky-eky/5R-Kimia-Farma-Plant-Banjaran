<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoSale extends Model
{
    protected $fillable = [
        'go_action_id',
        'dbr_index',
        'dbr_snapshot',
        'seller_user_id',
        'seller_bagian',
        'buyer_user_id',
        'buyer_bagian',
        'agreed_price',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'dbr_snapshot' => 'array',
        'agreed_price' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function goAction(): BelongsTo
    {
        return $this->belongsTo(GoAction::class);
    }

    public function sellerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function buyerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }
}
