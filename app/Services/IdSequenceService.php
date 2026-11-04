<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\CashTransaction;
use App\Models\HomepageSlide;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class IdSequenceService
{
    /**
     * All auto-increment tables managed by the application.
     */
    protected static array $managedTables = [
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

    /**
     * Ensure AUTO_INCREMENT is set to MAX(id) + 1 (never jumping) before inserting records.
     * Must only run outside an active DB transaction because ALTER TABLE is DDL in MySQL.
     */
    public static function ensureAutoIncrement(array $tables = []): void
    {
        try {
            $connection = DB::connection();
            if ($connection->getDriverName() !== 'mysql' || $connection->transactionLevel() > 0) {
                return;
            }

            $targetTables = !empty($tables) ? $tables : self::$managedTables;
            foreach ($targetTables as $table) {
                DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
            }
        } catch (\Throwable $e) {
            // Fail gracefully without interrupting user flow
        }
    }

    /**
     * Re-sequence primary keys (1, 2, 3, ...) across tables if any gaps exist, and reset AUTO_INCREMENT.
     * Preserves all foreign key relationships. Only runs outside an active transaction.
     */
    public static function resequenceAll(): void
    {
        try {
            $connection = DB::connection();
            if ($connection->getDriverName() !== 'mysql' || $connection->transactionLevel() > 0) {
                return;
            }

            if (self::hasAnyIdGap()) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                try {
                    self::resequenceMediaFiles();
                    self::resequenceMembers();
                    self::resequenceSimpleTable('member_photos', 'member_id');
                    self::resequenceUsers();
                    self::resequenceActivities();
                    self::resequenceCashTransactions();
                    self::resequenceSimpleTable('birthday_letters');
                    self::resequenceHomepageSlides();
                    self::resequenceSimpleTable('homepage_slide_photos', 'homepage_slide_id');
                    self::resequenceGameRounds();
                    self::resequenceSimpleTable('game_answers', 'game_round_id');
                    self::resequenceSimpleTable('audit_logs');
                } finally {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                }
            }

            self::ensureAutoIncrement();
        } catch (\Throwable $e) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Throwable $ignored) {
            }
        }
    }

    /**
     * Fast check if any table has non-contiguous IDs (where COUNT(*) != MAX(id)).
     */
    protected static function hasAnyIdGap(): bool
    {
        foreach (self::$managedTables as $table) {
            $stats = DB::selectOne("SELECT COUNT(*) AS cnt, COALESCE(MAX(id), 0) AS max_id FROM `{$table}`");
            if ($stats && (int) $stats->cnt !== (int) $stats->max_id) {
                return true;
            }
        }
        return false;
    }

    protected static function tableHasGap(string $table): bool
    {
        $stats = DB::selectOne("SELECT COUNT(*) AS cnt, COALESCE(MAX(id), 0) AS max_id FROM `{$table}`");
        return $stats && (int) $stats->cnt !== (int) $stats->max_id;
    }

    protected static function resequenceMembers(): void
    {
        if (!self::tableHasGap('members')) {
            return;
        }
        $rows = DB::table('members')->orderBy('id')->get(['id']);
        $newId = 1;
        foreach ($rows as $row) {
            $oldId = (int) $row->id;
            if ($oldId !== $newId) {
                DB::table('members')->where('id', $oldId)->update(['id' => $newId]);
                DB::table('member_photos')->where('member_id', $oldId)->update(['member_id' => $newId]);
                DB::table('users')->where('member_id', $oldId)->update(['member_id' => $newId]);
                DB::table('cash_transactions')->where('member_id', $oldId)->update(['member_id' => $newId]);
                DB::table('birthday_letters')->where('member_id', $oldId)->update(['member_id' => $newId]);
                DB::table('media_files')->where('fileable_type', Member::class)->where('fileable_id', $oldId)->update(['fileable_id' => $newId]);
                DB::table('audit_logs')->where('entity_type', 'member')->where('entity_id', $oldId)->update(['entity_id' => $newId]);
            }
            $newId++;
        }
    }

    protected static function resequenceUsers(): void
    {
        if (!self::tableHasGap('users')) {
            return;
        }
        $rows = DB::table('users')->orderBy('id')->get(['id']);
        $newId = 1;
        foreach ($rows as $row) {
            $oldId = (int) $row->id;
            if ($oldId !== $newId) {
                DB::table('users')->where('id', $oldId)->update(['id' => $newId]);
                DB::table('cash_transactions')->where('user_id', $oldId)->update(['user_id' => $newId]);
                DB::table('birthday_letters')->where('user_id', $oldId)->update(['user_id' => $newId]);
                DB::table('audit_logs')->where('user_id', $oldId)->update(['user_id' => $newId]);
                DB::table('audit_logs')->whereIn('entity_type', ['user', 'admin'])->where('entity_id', $oldId)->update(['entity_id' => $newId]);
            }
            $newId++;
        }
    }

    protected static function resequenceActivities(): void
    {
        if (!self::tableHasGap('activities')) {
            return;
        }
        $rows = DB::table('activities')->orderBy('id')->get(['id']);
        $newId = 1;
        foreach ($rows as $row) {
            $oldId = (int) $row->id;
            if ($oldId !== $newId) {
                DB::table('activities')->where('id', $oldId)->update(['id' => $newId]);
                DB::table('media_files')->where('fileable_type', Activity::class)->where('fileable_id', $oldId)->update(['fileable_id' => $newId]);
                DB::table('audit_logs')->where('entity_type', 'activity')->where('entity_id', $oldId)->update(['entity_id' => $newId]);
            }
            $newId++;
        }
    }

    protected static function resequenceCashTransactions(): void
    {
        if (!self::tableHasGap('cash_transactions')) {
            return;
        }
        $rows = DB::table('cash_transactions')->orderBy('id')->get(['id']);
        $newId = 1;
        foreach ($rows as $row) {
            $oldId = (int) $row->id;
            if ($oldId !== $newId) {
                DB::table('cash_transactions')->where('id', $oldId)->update(['id' => $newId]);
                DB::table('media_files')->where('fileable_type', CashTransaction::class)->where('fileable_id', $oldId)->update(['fileable_id' => $newId]);
                DB::table('audit_logs')->where('entity_type', 'cash_transaction')->where('entity_id', $oldId)->update(['entity_id' => $newId]);
            }
            $newId++;
        }
    }

    protected static function resequenceHomepageSlides(): void
    {
        if (!self::tableHasGap('homepage_slides')) {
            return;
        }
        $rows = DB::table('homepage_slides')->orderBy('id')->get(['id']);
        $newId = 1;
        foreach ($rows as $row) {
            $oldId = (int) $row->id;
            if ($oldId !== $newId) {
                DB::table('homepage_slides')->where('id', $oldId)->update(['id' => $newId]);
                DB::table('homepage_slide_photos')->where('homepage_slide_id', $oldId)->update(['homepage_slide_id' => $newId]);
                DB::table('media_files')->where('fileable_type', HomepageSlide::class)->where('fileable_id', $oldId)->update(['fileable_id' => $newId]);
                DB::table('audit_logs')->where('entity_type', 'homepage_slide')->where('entity_id', $oldId)->update(['entity_id' => $newId]);
            }
            $newId++;
        }
    }

    protected static function resequenceMediaFiles(): void
    {
        if (!self::tableHasGap('media_files')) {
            return;
        }
        $rows = DB::table('media_files')->orderBy('id')->get(['id']);
        $newId = 1;
        foreach ($rows as $row) {
            $oldId = (int) $row->id;
            if ($oldId !== $newId) {
                DB::table('media_files')->where('id', $oldId)->update(['id' => $newId]);
                DB::table('members')->where('photo_file_id', $oldId)->update(['photo_file_id' => $newId]);
                DB::table('cash_transactions')->where('proof_file_id', $oldId)->update(['proof_file_id' => $newId]);
                DB::table('homepage_slides')->where('media_file_id', $oldId)->update(['media_file_id' => $newId]);
                DB::table('game_rounds')->where('media_file_id', $oldId)->update(['media_file_id' => $newId]);
            }
            $newId++;
        }
    }

    protected static function resequenceGameRounds(): void
    {
        if (!self::tableHasGap('game_rounds')) {
            return;
        }
        $rows = DB::table('game_rounds')->orderBy('id')->get(['id']);
        $newId = 1;
        foreach ($rows as $row) {
            $oldId = (int) $row->id;
            if ($oldId !== $newId) {
                DB::table('game_rounds')->where('id', $oldId)->update(['id' => $newId]);
                DB::table('game_answers')->where('game_round_id', $oldId)->update(['game_round_id' => $newId]);
            }
            $newId++;
        }
    }

    protected static function resequenceSimpleTable(string $table, ?string $secondaryOrderCol = null): void
    {
        if (!self::tableHasGap($table)) {
            return;
        }
        $query = DB::table($table);
        if ($secondaryOrderCol) {
            $query->orderBy($secondaryOrderCol);
        }
        $rows = $query->orderBy('id')->get(['id']);

        $offset = ((int) DB::table($table)->max('id')) + 10000;
        foreach ($rows as $row) {
            DB::table($table)->where('id', $row->id)->update(['id' => ((int) $row->id) + $offset]);
        }

        $newId = 1;
        foreach ($rows as $row) {
            DB::table($table)->where('id', ((int) $row->id) + $offset)->update(['id' => $newId]);
            $newId++;
        }
    }
}
