<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER posts_type_check_insert
                BEFORE INSERT ON posts
                WHEN NEW.type NOT IN (0, 1, 2, 3)
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid assignment type.');
                END
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER posts_type_check_update
                BEFORE UPDATE OF type ON posts
                WHEN NEW.type NOT IN (0, 1, 2, 3)
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid assignment type.');
                END
            SQL);

            return;
        }

        DB::statement('ALTER TABLE posts ADD CONSTRAINT posts_type_check CHECK (type IN (0, 1, 2, 3))');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS posts_type_check_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS posts_type_check_update');

            return;
        }

        try {
            DB::statement('ALTER TABLE posts DROP CHECK posts_type_check');
        } catch (QueryException) {
            // MariaDB uses DROP CONSTRAINT for named CHECK constraints.
            DB::statement('ALTER TABLE posts DROP CONSTRAINT posts_type_check');
        }
    }
};
