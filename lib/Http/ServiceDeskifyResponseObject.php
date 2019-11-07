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
        $this->response->withStatus($code, $reasonPhrase);
    }

    public function getReasonPhrase()
    {
        $this->response->getReasonPhrase();
    }

    public function getBody()
    {
        return $this->response->getBody()->getContents();
    }

    public function getBodyAsJson()
    {
        return json_encode($this->response->getBody()->getContents());
    }

    public function getProtocolVersion()
    {
        return $this->response->getProtocolVersion();
    }

    public function isErrorResponse()
    {
        $status = $this->response->getStatusCode();
        return $status >= 400;
    }

    public function getError()
    {
        return $this->isErrorResponse() ? $this->getBody() : null;
    }

    public function withProtocolVersion($version)
    {
        return $this->response->withProtocolVersion();
    }

    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    public function hasHeader($name)
    {
        $this->response->hasHeader();
    }

    public function getHeader($name)
    {
        $this->response->getHeader();
    }

    public function getHeaderLine($name)
    {
        $this->response->getHeaderLine();
    }

    public function withHeader($name, $value)
    {
        $this->response->withHeader();
    }

    public function withAddedHeader($name, $value)
    {
        $this->response->withAddedHeader();
    }

    public function withoutHeader($name)
    {
        $this->response->withoutHeader();
    }

    public function withBody(StreamInterface $body)
    {
        $this->response->withBody();
    }
}