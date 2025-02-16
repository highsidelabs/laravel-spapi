<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cache', function (Blueprint $table) {
            $table->string('key', 511)->change();
            $table->string('value', 2559)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cache', function (Blueprint $table) {
            // This will throw an error if there are values longer than 255 characters in the cache table,
            // but if we automatically truncated them there would be silent data loss
            $table->string('key', 255)->change();
            $table->string('value', 255)->change();
        });
    }
};
