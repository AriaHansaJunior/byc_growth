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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contributor_name')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('type')->default('inflow'); // 'inflow', 'outflow'
            $table->text('description')->nullable();
            $table->foreignId('proof_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->date('transaction_date');
            $table->timestamps();

            $table->index(['transaction_date', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
