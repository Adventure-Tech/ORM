<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private const TABLE_NAME = 'select';
    public function up(): void
    {
        Schema::create(self::TABLE_NAME, static function (Blueprint $table) {
            $table->id();
            $table->ulid('end');
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE_NAME);
    }
};
