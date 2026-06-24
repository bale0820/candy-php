<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('category')->default('Laravel')->after('content');
            $table->json('tags')->nullable()->after('category');
            $table->string('author')->default('anonymous')->after('tags');
            $table->unsignedInteger('replies')->default(0)->after('author');
            $table->unsignedInteger('views')->default(0)->after('replies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['category', 'tags', 'author', 'replies', 'views']);
        });
    }
};
