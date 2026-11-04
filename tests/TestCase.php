<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        $pdo = null;
        try {
            $connection = DB::connection();
            if ($connection->getDriverName() === 'mysql') {
                $pdo = $connection->getPdo();
            }
        } catch (\Throwable $e) {
            $pdo = null;
        }

        parent::tearDown();

        if ($pdo !== null) {
            try {
                $tables = [
                    'activities',
                    'audit_logs',
                    'birthday_letters',
                    'cash_transactions',
                    'game_answers',
                    'game_rounds',
                    'game_scores',
                    'game_states',
                    'games',
                    'homepage_slide_photos',
                    'homepage_slides',
                    'media_files',
                    'member_photos',
                    'members',
                    'teams',
                    'users',
                ];
                foreach ($tables as $table) {
                    $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                }
            } catch (\Throwable $e) {
                // Ignore if connection is closed
            }
        }
    }
}
