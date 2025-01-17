<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('unit_price');
            $table->string('bulk_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('purchase_goal_id');
            $table->timestamps();

            $table->foreign('purchase_goal_id')->references('id')->on('purchase_goals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
