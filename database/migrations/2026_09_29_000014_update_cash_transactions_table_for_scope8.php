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
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('user_id')->constrained('members')->nullOnDelete();
            $table->string('account_type')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropColumn(['member_id', 'account_type']);
        });
    }
};
