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
        // 1. Add permissions column to users table
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('permissions')->nullable()->after('role');
            });
        }

        // 2. Add last_action audit columns to tracked entity tables
        $trackedTables = ['members', 'activities', 'cash_transactions', 'homepage_slides', 'birthday_letters'];
        foreach ($trackedTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'last_action_by')) {
                        $table->string('last_action_by')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'last_action_type')) {
                        $table->string('last_action_type')->nullable(); // 'created' or 'edited'
                    }
                    if (!Schema::hasColumn($tableName, 'last_action_at')) {
                        $table->timestamp('last_action_at')->nullable();
                    }
                });
            }
        }

        // 3. Create audit_logs table for universal history tracking
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_email')->index();
                $table->string('action'); // 'created', 'edited', 'deleted', 'batch_deleted'
                $table->string('entity_type')->index(); // 'member', 'activity', 'cash_transaction', 'roles', etc.
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        // 4. Seed all existing admin users with full access to all 7 modules
        $allPermissions = json_encode([
            'homepage',
            'activities',
            'members',
            'games',
            'birthday_wishes',
            'cash_management',
            'roles',
        ]);

        DB::table('users')
            ->where('role', 'admin')
            ->whereNull('permissions')
            ->update(['permissions' => $allPermissions]);

        // 5. Backfill existing members with initial audit information if null
        if (Schema::hasTable('members')) {
            DB::table('members')
                ->whereNull('last_action_by')
                ->update([
                    'last_action_by' => 'admin@gmail.com',
                    'last_action_type' => 'created',
                    'last_action_at' => DB::raw('created_at'),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('audit_logs')) {
            Schema::dropIfExists('audit_logs');
        }

        $trackedTables = ['members', 'activities', 'cash_transactions', 'homepage_slides', 'birthday_letters'];
        foreach ($trackedTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $colsToDrop = [];
                    if (Schema::hasColumn($tableName, 'last_action_by')) {
                        $colsToDrop[] = 'last_action_by';
                    }
                    if (Schema::hasColumn($tableName, 'last_action_type')) {
                        $colsToDrop[] = 'last_action_type';
                    }
                    if (Schema::hasColumn($tableName, 'last_action_at')) {
                        $colsToDrop[] = 'last_action_at';
                    }
                    if (!empty($colsToDrop)) {
                        $table->dropColumn($colsToDrop);
                    }
                });
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }
    }
};
