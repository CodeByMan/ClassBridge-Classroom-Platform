<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateGoogleIds = DB::table('users')
            ->select('google_id')
            ->whereNotNull('google_id')
            ->groupBy('google_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('google_id');

        if ($duplicateGoogleIds->isNotEmpty()) {
            throw new RuntimeException(
                'Duplicate Google provider IDs exist. Resolve the affected user accounts before rerunning this migration.'
            );
        }

        $this->removeDuplicateGroupMemberships();
        $this->removeDuplicatePostSubmissions();

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('google_id');
        });

        Schema::table('group_user', function (Blueprint $table): void {
            $table->unique(['group_id', 'user_id']);
        });

        Schema::table('post_user', function (Blueprint $table): void {
            $table->unique(['post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('post_user', function (Blueprint $table): void {
            $table->dropUnique(['post_id', 'user_id']);
        });

        Schema::table('group_user', function (Blueprint $table): void {
            $table->dropUnique(['group_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
        });
    }

    private function removeDuplicateGroupMemberships(): void
    {
        $duplicates = DB::table('group_user')
            ->select('group_id', 'user_id')
            ->groupBy('group_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('group_user')
                ->where('group_id', $duplicate->group_id)
                ->where('user_id', $duplicate->user_id)
                ->delete();

            DB::table('group_user')->insert([
                'group_id' => $duplicate->group_id,
                'user_id' => $duplicate->user_id,
            ]);
        }
    }

    private function removeDuplicatePostSubmissions(): void
    {
        $duplicates = DB::table('post_user')
            ->select('post_id', 'user_id')
            ->groupBy('post_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $submissionToKeep = DB::table('post_user')
                ->where('post_id', $duplicate->post_id)
                ->where('user_id', $duplicate->user_id)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            DB::table('post_user')
                ->where('post_id', $duplicate->post_id)
                ->where('user_id', $duplicate->user_id)
                ->where('id', '!=', $submissionToKeep->id)
                ->delete();
        }
    }
};
