<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private const TABLE_NAME = 'posts';
    public function up(): void
    {
        Schema::create(self::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('number', ['ONE', 'TWO']);
            $table->timestampTz('published_at')->nullable();
            $table->string('published_tz')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreignId('author')->constrained('users');
            $table->foreignId('editor')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE_NAME);
    }
};
