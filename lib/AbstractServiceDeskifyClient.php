<?php

namespace VmeRetail\ServiceDeskify;

use GuzzleHttp\Client;
// todo we do not need this anymore
abstract class AbstractServiceDeskifyClient
{
    /**
     * @var Client
     */
    public $http;

    /**
     * @var string
     */
    public $baseUrl;

    public function __construct(){
        $this->http = new Client();
    }

    public function setBaseUrl(string $url)
    {
        $this->baseUrl = $url;
        return $this;
    }
}