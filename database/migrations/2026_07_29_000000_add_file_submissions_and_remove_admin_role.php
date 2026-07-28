<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove only the known local demo administrator account from earlier releases.
        DB::table('users')->where('email', 'admin@classbridge.test')->delete();

        if (Schema::hasColumn('users', 'admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('admin');
            });
        }

        if (! Schema::hasColumn('posts', 'total_marks')) {
            Schema::table('posts', function (Blueprint $table): void {
                $table->unsignedSmallInteger('total_marks')->nullable()->after('deadline');
            });
        }

        if (! Schema::hasColumn('post_user', 'submission_path')) {
            Schema::table('post_user', function (Blueprint $table): void {
                $table->string('submission_path')->nullable()->after('post_answer');
                $table->string('submission_name')->nullable()->after('submission_path');
                $table->unsignedSmallInteger('grade')->nullable()->after('submission_name');
                $table->timestamp('graded_at')->nullable()->after('grade');
            });
        }

        $this->replacePostTypeConstraint([0, 1, 2, 3]);
    }

    public function down(): void
    {
        $this->replacePostTypeConstraint([0, 1, 2]);

        $columns = array_values(array_filter([
            Schema::hasColumn('post_user', 'submission_path') ? 'submission_path' : null,
            Schema::hasColumn('post_user', 'submission_name') ? 'submission_name' : null,
            Schema::hasColumn('post_user', 'grade') ? 'grade' : null,
            Schema::hasColumn('post_user', 'graded_at') ? 'graded_at' : null,
        ]));

        if ($columns !== []) {
            Schema::table('post_user', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }

        if (Schema::hasColumn('posts', 'total_marks')) {
            Schema::table('posts', function (Blueprint $table): void {
                $table->dropColumn('total_marks');
            });
        }
    }

    private function replacePostTypeConstraint(array $allowedTypes): void
    {
        $allowed = implode(', ', $allowedTypes);

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS posts_type_check_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS posts_type_check_update');

            DB::unprepared("CREATE TRIGGER posts_type_check_insert
                BEFORE INSERT ON posts
                WHEN NEW.type NOT IN ({$allowed})
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid assignment type.');
                END");

            DB::unprepared("CREATE TRIGGER posts_type_check_update
                BEFORE UPDATE OF type ON posts
                WHEN NEW.type NOT IN ({$allowed})
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid assignment type.');
                END");

            return;
        }

        try {
            DB::statement('ALTER TABLE posts DROP CHECK posts_type_check');
        } catch (QueryException) {
            try {
                DB::statement('ALTER TABLE posts DROP CONSTRAINT posts_type_check');
            } catch (QueryException) {
                // The constraint may not exist on an older installation.
            }
        }

        DB::statement("ALTER TABLE posts ADD CONSTRAINT posts_type_check CHECK (type IN ({$allowed}))");
    }
};
