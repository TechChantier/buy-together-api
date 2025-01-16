<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseGoal extends Model
{
    protected $fillable = [
        'title',
        'description',
        'target_amount',
        'amount_per_person',
        'group_link',
        'start_date',
        'end_date',
        'creator_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function product(): HasOne
    {
        return $this->hasOne(Product::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'user_in_purchase_goals', 'purchase_goal_id', 'user_id')
            ->withPivot('joined_at', 'contributed_amount');
    }
}
