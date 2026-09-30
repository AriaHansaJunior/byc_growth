<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('birthday_letters', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('member_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('birthday_year')
                ->nullable()
                ->after('user_id');

            $table->boolean('is_anonymous')
                ->default(false)
                ->after('birthday_year');

            $table->unique(['user_id', 'member_id', 'birthday_year'], 'unique_sender_recipient_year');
        });

        // Backfill birthday_year for existing historical records from created_at
        DB::table('birthday_letters')
            ->whereNull('birthday_year')
            ->update([
                'birthday_year' => DB::raw('YEAR(created_at)')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('birthday_letters', function (Blueprint $table) {
            $table->dropUnique('unique_sender_recipient_year');
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'birthday_year', 'is_anonymous']);
        });
    }
};
