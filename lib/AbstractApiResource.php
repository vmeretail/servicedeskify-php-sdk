<?php

namespace VmeRetail\ServiceDeskify;


use GuzzleHttp\Psr7\Response;
use VmeRetail\ServiceDeskify\Http\AuthenticatedClientSingleton;
use VmeRetail\ServiceDeskify\Http\ServiceDeskifyResponseObject;

abstract class AbstractApiResource
{
    /**
     * @var null|AuthenticatedClientSingleton
     */
    protected $authenticated;

    /**
     * @var Response
     */
    protected $lastResponse;

    public function __construct()
    {
        if (!AuthenticatedClientSingleton::instance()->isValid()) {
            // todo throw an exception because no one is authenticated yet
            throw new \Exception('');
        }
        $this->authenticated = AuthenticatedClientSingleton::instance();
    }

    /**
     * @param array $query
     * @return ServiceDeskifyResponseObject
     * Http Method GET
     */
    public function all(array $query)
    {
        $response = $this->lastResponse = $this->authenticated->http->get($this->url(), [
            'headers' => $this->authenticated->headers(),
            'query' => $query
        ]);

        return (new ServiceDeskifyResponseObject($response));
    }

    /**
     * @param array $query
     * @return ServiceDeskifyResponseObject
     * Http Method GET
     */
    public function retrieve()
    {

    }

    /**
     * @param array $properties
     * @return ServiceDeskifyResponseObject
     * Http Method POST
     */
    public function create(array $properties)
    {
        $response = $this->authenticated->http->post($this->url(), [
            'headers' => array_merge(
                $this->authenticated->headers(),
                [ 'Content-Type' => 'application/json']
            ),
            'json' => $properties
        ]);

        return (new ServiceDeskifyResponseObject($response));
    }

    /**
     * @param array $query
     * @return ServiceDeskifyResponseObject
     * Http Method PUT
     */
    public function update()
    {

    }

    /**
     * @param array $query
     * @return ServiceDeskifyResponseObject
     * Http Method DELETE
     */
    public function destroy()
    {

    }

    public function url()
    {
        // e.g. incidents.posts --> [ incidents, posts ]
        $nested = explode('.', $this->resource());

        if (count($nested) > 1) {
            // todo figure this out
            // e.g. /api/incidents/{}/posts
            // this could be
            $resource = $nested[0];
            return $this->authenticated->baseUrl . '/api/' . $nested[0] . '/' . $this->{$resource}() . '/' . $nested[1];
        }

        return  $this->authenticated->baseUrl . '/api/' . $this->resource();
    }

    public abstract function resource();
}