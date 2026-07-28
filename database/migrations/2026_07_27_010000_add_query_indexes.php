<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->index(['group_id', 'deadline'], 'posts_group_deadline_index');
            $table->index(['group_id', 'created_at'], 'posts_group_created_index');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->index(['post_id', 'created_at'], 'comments_post_created_index');
        });

        Schema::table('post_user', function (Blueprint $table): void {
            $table->index(['post_id', 'finished', 'updated_at'], 'post_user_review_index');
        });
    }

    public function down(): void
    {
        Schema::table('post_user', function (Blueprint $table): void {
            $table->dropIndex('post_user_review_index');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex('comments_post_created_index');
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex('posts_group_deadline_index');
            $table->dropIndex('posts_group_created_index');
        });
    }
};
