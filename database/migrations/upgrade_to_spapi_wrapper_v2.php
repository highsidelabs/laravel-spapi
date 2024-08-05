<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpgradeToSpApiWrapperV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('spapi_credentials', function (Blueprint $table) {
            $dropColumns = ['access_key_id', 'secret_access_key', 'role_arn'];

            foreach ($dropColumns as $col) {
                if (Schema::hasColumn('spapi_credentials', $col) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Since the AWS columns were config-dependent to begin with, and the config
        // option that determined their presence is deprecated, we're not defining
        // a down migration.
    }
}
