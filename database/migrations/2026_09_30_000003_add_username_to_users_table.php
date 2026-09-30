<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds unique username to users table and populates existing users.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 60)->nullable()->after('name');
            });
        }

        // 1. Ensure the two required primary admin accounts exist and have exact usernames & credentials
        // Account 1: admin@gmail.com / admin_utama / admin123
        $admin1 = DB::table('users')->where('email', 'admin@gmail.com')->first();
        if ($admin1) {
            DB::table('users')->where('id', $admin1->id)->update([
                'username' => 'admin_utama',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]);
        } else {
            DB::table('users')->insert([
                'name' => 'Admin Utama',
                'username' => 'admin_utama',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Account 2: jojo_ganteng@gmail.com / rilbiezzz / jojo123
        $admin2 = DB::table('users')->where('email', 'jojo_ganteng@gmail.com')->first();
        if ($admin2) {
            DB::table('users')->where('id', $admin2->id)->update([
                'username' => 'rilbiezzz',
                'password' => Hash::make('jojo123'),
                'role' => 'admin',
            ]);
        } else {
            DB::table('users')->insert([
                'name' => 'Jojo Admin',
                'username' => 'rilbiezzz',
                'email' => 'jojo_ganteng@gmail.com',
                'password' => Hash::make('jojo123'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Keep legacy admin_byc@gmail.com populated with a unique username if present
        $legacyAdmin = DB::table('users')->where('email', 'admin_byc@gmail.com')->first();
        if ($legacyAdmin && empty($legacyAdmin->username)) {
            DB::table('users')->where('id', $legacyAdmin->id)->update([
                'username' => 'admin_byc',
            ]);
        }

        // 2. Populate valid unique usernames for any existing users without a username
        $existingUsernames = DB::table('users')->whereNotNull('username')->pluck('username')->toArray();
        $unassignedUsers = DB::table('users')->whereNull('username')->get();

        foreach ($unassignedUsers as $user) {
            $base = !empty($user->email) ? explode('@', $user->email)[0] : 'user';
            $base = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $base) ?: 'user_' . $user->id);
            $candidate = $base;
            $counter = 1;
            while (in_array($candidate, $existingUsernames)) {
                $candidate = $base . '_' . $counter;
                $counter++;
            }
            $existingUsernames[] = $candidate;

            DB::table('users')->where('id', $user->id)->update([
                'username' => $candidate,
            ]);
        }

        // 3. Apply unique constraint and index
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
