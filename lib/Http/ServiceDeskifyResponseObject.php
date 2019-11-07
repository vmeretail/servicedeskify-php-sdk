<?php

namespace VmeRetail\ServiceDeskify\Http;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class ServiceDeskifyResponseObject
 * @package VmeRetail\ServiceDeskify
 */
class ServiceDeskifyResponseObject implements ResponseInterface
{
    protected $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    public function withStatus($code, $reasonPhrase = '')
    {

    }

    public function getReasonPhrase()
    {
        $this->response->getReasonPhrase();
    }

    public function getBody()
    {

    }

    // TODO Very Important
    public function getBodyAsJson()
    {

    }

    public function getProtocolVersion()
    {

    }

    // TODO Very Important
    public function isErrorResponse()
    {

    }

    // TODO Very Important
    public function getError()
    {

    }

    //
    public function withProtocolVersion($version)
    {

    }

    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    public function hasHeader($name)
    {
        // TODO: Implement hasHeader() method.
    }

    public function getHeader($name)
    {
        // TODO: Implement getHeader() method.
    }

    public function getHeaderLine($name)
    {
        // TODO: Implement getHeaderLine() method.
    }

    public function withHeader($name, $value)
    {
        // TODO: Implement withHeader() method.
    }

    public function withAddedHeader($name, $value)
    {
        // TODO: Implement withAddedHeader() method.
    }

    public function withoutHeader($name)
    {
        // TODO: Implement withoutHeader() method.
    }

    public function withBody(StreamInterface $body)
    {
        // TODO: Implement withBody() method.
    }
}