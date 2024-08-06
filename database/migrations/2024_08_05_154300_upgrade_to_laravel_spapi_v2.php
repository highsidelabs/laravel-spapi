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
        $dropColumns = ['access_key_id', 'secret_access_key', 'role_arn'];

        foreach ($dropColumns as $col) {
            if (Schema::hasColumn('spapi_credentials', $col)) {
                Schema::table('spapi_credentials', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Since the AWS columns were config-dependent to begin with, and the config
        // option that determined their presence is deprecated, we're not defining
        // a down migration.
    }
};
