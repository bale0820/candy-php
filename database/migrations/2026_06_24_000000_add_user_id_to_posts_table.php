<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });

        DB::statement('
            update posts
            set user_id = users.id
            from users
            where posts.user_id is null
              and posts.author = users.name
        ');
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
