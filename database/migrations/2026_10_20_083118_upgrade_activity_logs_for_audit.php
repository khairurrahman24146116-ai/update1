<?php

use App\Support\ActivityLogCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->renameColumn('model_type', 'target_type');
            $table->renameColumn('model_id', 'target_id');
            $table->renameColumn('payload', 'metadata');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('user_name')->nullable()->after('user_id');
            $table->string('user_role')->nullable()->after('user_name');
            $table->string('module')->nullable()->after('action');
            $table->string('description')->nullable()->after('module');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->index('created_at');
            $table->index(['module', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        $this->backfillContext();

        $removed = $this->removeDuplicateLogins();

        if ($removed > 0) {
            DB::table('activity_logs')->insert([
                'user_id' => null,
                'user_name' => 'Sistem',
                'user_role' => 'system',
                'action' => 'system.maintenance',
                'module' => 'system',
                'description' => 'Membersihkan log login duplikat',
                'target_type' => null,
                'target_id' => null,
                'metadata' => json_encode([
                    'removed_duplicates' => $removed,
                    'reason' => 'duplicate_auth_listeners',
                ]),
                'ip_address' => null,
                'user_agent' => null,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['module', 'created_at']);
            $table->dropIndex(['action', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropColumn(['user_name', 'user_role', 'module', 'description', 'ip_address', 'user_agent']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->renameColumn('target_type', 'model_type');
            $table->renameColumn('target_id', 'model_id');
            $table->renameColumn('metadata', 'payload');
        });
    }

    private function backfillContext(): void
    {
        DB::table('activity_logs')
            ->select(['id', 'user_id', 'action'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $userIds = $rows->pluck('user_id')->filter()->unique()->values();
                $users = $userIds->isEmpty()
                    ? collect()
                    : DB::table('users')->whereIn('id', $userIds)->get()->keyBy('id');

                foreach ($rows as $row) {
                    $user = $row->user_id ? $users->get($row->user_id) : null;

                    DB::table('activity_logs')->where('id', $row->id)->update([
                        'user_name' => $user?->name,
                        'user_role' => $user?->role,
                        'module' => ActivityLogCatalog::moduleFor($row->action),
                        'description' => ActivityLogCatalog::descriptionFor($row->action),
                    ]);
                }
            });
    }

    private function removeDuplicateLogins(): int
    {
        $seen = [];
        $duplicates = [];

        DB::table('activity_logs')
            ->select(['id', 'user_id', 'created_at', 'metadata', 'ip_address', 'user_agent'])
            ->where('action', 'login')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$seen, &$duplicates): void {
                foreach ($rows as $row) {
                    $key = hash('sha256', implode('|', [
                        (string) $row->user_id,
                        (string) $row->created_at,
                        (string) $row->metadata,
                        (string) $row->ip_address,
                        (string) $row->user_agent,
                    ]));

                    if (isset($seen[$key])) {
                        $duplicates[] = $row->id;

                        continue;
                    }

                    $seen[$key] = true;
                }
            });

        foreach (array_chunk($duplicates, 500) as $chunk) {
            DB::table('activity_logs')->whereIn('id', $chunk)->delete();
        }

        return count($duplicates);
    }
};
