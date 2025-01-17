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
        Schema::create('purchase_goals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('creator_id');
            $table->decimal('target_amount', 10, 2);
            $table->decimal('amount_per_person', 10, 2)->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->string('group_link');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_goals');
    }
};
