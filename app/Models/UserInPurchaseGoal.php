<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInPurchaseGoal extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'purchase_goal_id',
        'joined_at',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseGoal()
    {
        return $this->belongsTo(PurchaseGoal::class);
    }
}
