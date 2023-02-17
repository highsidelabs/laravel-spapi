<?php

namespace HighsideLabs\LaravelSpApi;

use Exception;
use SellingPartnerApi\Configuration as SpApiConfiguration;

class Configuration extends SpApiConfiguration
{
    public $placeholder;

    public function __construct(bool $placeholder, ...$args)
    {
        parent::__construct(...$args);
        $this->placeholder = $placeholder;
    }

    public function signRequest($request, $scope = null, $restrictedPath = null, $operation = null)
    {
        if ($this->placeholder) {
            throw new Exception(
                'You must call Credentials::useOn($apiInstance) before calling any API methods.'
            );
        }
        $this->placeholder = false;
        return parent::signRequest($request, $scope, $restrictedPath, $operation);
    }
}
