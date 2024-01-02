<?php

namespace VmeRetail\ServiceDeskify\Http;


use Psr\Http\Message\MessageInterface;
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

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->response->withStatus($code, $reasonPhrase);
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function getDecodedBody()
    {
        return json_decode($this->getBody()->getContents());
    }

    public function getBodyAsJson()
    {
        return json_encode($this->response->getBody()->getContents());
    }

    public function getProtocolVersion(): string
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

    public function withProtocolVersion(string $version): MessageInterface
    {
        return $this->response->withProtocolVersion($version);
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        return $this->response->withHeader($name, $value);
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->response->withAddedHeader($name, $value);
    }

    public function withoutHeader(string $name): MessageInterface
    {
        return $this->response->withoutHeader($name);
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return $this->response->withBody($body);
    }
}
