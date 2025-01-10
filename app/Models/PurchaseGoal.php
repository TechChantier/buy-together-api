<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseGoal extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'amount',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
