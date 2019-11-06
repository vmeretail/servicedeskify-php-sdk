<?php

namespace VmeRetail\ServiceDeskify\ServiceDeskifyClient;

use GuzzleHttp\Client;

class AuthenticatedClientSingleton
{
    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var
     */
    protected $refreshToken;

    /**
     * @var
     */
    protected $expiresIn;

    /**
     * @var
     */
    protected $tokenType;

    /**
     * @var
     */
    public $baseUrl;

    /**
     * @var
     */
    public $http;

    /**
     * @var null
     */
    private static $instance = null;

    private function __construct(){}

    public function setApiToken($token)
    {
        $this->token = $token;
    }

    public function headers()
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => $this->tokenType . ' ' . $this->accessToken
        ];
    }

    public static function instance()
    {
        if (self::$instance == null)
        {
            self::$instance = new AuthenticatedClientSingleton();
            self::$instance->http = new Client();
        }

        return self::$instance;
    }

    public function setTokenDetails(array $tokenDetails)
    {
        $this->accessToken = $tokenDetails['access_token'];
        $this->refreshToken = $tokenDetails['refresh_token'];
        $this->expiresIn = $tokenDetails['expires_in'];
        $this->tokenType = $tokenDetails['token_type'];
    }

    public function setBaseUrl($baseUrl)
    {
        return $this->baseUrl = $baseUrl;
    }

    public function isValid()
    {
        return $this->accessToken && $this->refreshToken && $this->tokenType && $this->expiresIn;
    }
}