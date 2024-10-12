<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use SellingPartnerApi\Enums\Region;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spapi_credentials', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            /*
             * The Selling Partner ID/Merchant ID. This is returned in the OAuth response from Amazon when
             * authorizing a new seller on an SP API application. If self-authorizing an application, log
             * into Seller Central and go to the URL below to find the account's Selling Partner ID. Replace
             * <regional-seller-central-url> with your region's Seller Central domain, e.g. sellercentral.amazon.com,
             * sellercentral-europe.amazon.com, etc:
             *
             * https://<regional-seller-central-domain>/sw/AccountInfo/MerchantToken/step/MerchantToken
             */
            $table->string('selling_partner_id')->unique();

            // The SP API region that these credentials are for
            $table->enum('region', Region::values());

            // The app credentials that the the refresh token was created with
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();

            // The LWA refresh token for this set of credentials
            $table->string('refresh_token', 511);

            // The seller these credentials are associated with
            $table->foreignId('seller_id')->constrained('spapi_sellers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('spapi_credentials');
    }
};
