<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('post_user', 'returned_at')) {
            Schema::table('post_user', function (Blueprint $table): void {
                $table->timestamp('returned_at')->nullable()->after('graded_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('post_user', 'returned_at')) {
            Schema::table('post_user', function (Blueprint $table): void {
                $table->dropColumn('returned_at');
            });
        }
    }
};
