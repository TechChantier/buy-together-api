<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory; 
    
    protected $fillable = [
        'name',
        'description',
        'unit_price',
        'bulk_price',
        'quantity',
        'purchase_goal_id',
    ];

    public function purchaseGoal(): BelongsTo
    {
        return $this->belongsTo(PurchaseGoal::class);
    }
}
