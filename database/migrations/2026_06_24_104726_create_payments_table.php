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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->foreignId('fee_category_id')->constrained('fee_categories')->cascadeOnDelete(); 
            $table->integer('for_month');
            $table->integer('for_year');
            $table->integer('amount_paid')->default(0); 
            $table->enum('status', ['lunas', 'belum'])->default('belum');
            $table->date('payment_date')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
