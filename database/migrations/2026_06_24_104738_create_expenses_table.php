<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->enum('category', [
                'operasional',
                'perbaikan',
                'darurat',
                'lainnya'
            ]);

            $table->string('description');

            $table->unsignedBigInteger('amount');

            $table->date('expense_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};