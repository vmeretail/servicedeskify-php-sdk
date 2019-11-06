<?php

namespace VmeRetail\ServiceDeskify\HelpdeskClient;


use GuzzleHttp\Psr7\Response;
use VmeRetail\ServiceDeskify\ServiceDeskifyClient\AuthenticatedClientSingleton;
use VmeRetail\ServiceDeskify\ServiceDeskifyResponseObject;

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

    public function all(array $query)
    {
        $response = $this->lastResponse = $this->authenticated->http->get($this->url(), [
            'headers' => $this->authenticated->headers(),
            'query' => $query
        ]);

        return (new ServiceDeskifyResponseObject($response));
    }

    public function retrieve()
    {

    }

    public function create(array $properties)
    {
        $response = $this->authenticated->http->post($this->url(), [
            'headers' => array_merge(
                $this->authenticated->headers(),
                [ 'Content-Type' => 'application/json']
            )
        ]);

        return (new ServiceDeskifyResponseObject($response));
    }

    public function update()
    {

    }

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
            return '/api/' . $nested[0] . '/' . $this->{$resource}() . '/' . $nested[1];
        }

        return  $this->authenticated->baseUrl  . '/api/' . $this->resource();
    }

    public abstract function resource();
}