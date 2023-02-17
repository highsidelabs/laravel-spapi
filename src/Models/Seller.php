<?php

namespace HighsideLabs\LaravelSpApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seller extends Model
{
    protected $table = 'spapi_sellers';

    protected $fillable = ['name'];

    /**
     * Get all the Credentials connected to this Seller
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credentials::class);
    }
}
