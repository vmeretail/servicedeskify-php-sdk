<?php

namespace VmeRetail\ServiceDeskify;

use VmeRetail\ServiceDeskify\ServiceDeskifyClient\AuthenticatedClientSingleton;

class PublicClient extends AbstractServiceDeskifyClient
{
    /**
     * @param $username
     * @param $password
     * @param $client
     * @param $secret
     * @return null|AuthenticatedClientSingleton
     */
    public function authenticate($username, $password, $client, $secret)
    {
        $response = $this->http->post($this->baseUrl . '/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $client,
                'client_secret' => $secret,
                'username' => $username,
                'password' => $password,
                'scope' => '*'
            ]
        ]);

        // We got a set of tokens so now we can create an authenticated client
        $apiTokenAccess = json_decode($response->getBody(), true);

        // Get an instance
        $authenticated = AuthenticatedClientSingleton::instance();

        // The tokens are valid for the same base url
        $authenticated->setBaseUrl($this->baseUrl);

        // Fill in the token details
        $authenticated->setTokenDetails($apiTokenAccess);

        // Now we can return a valid authenticated client
        return $authenticated;
    }
}