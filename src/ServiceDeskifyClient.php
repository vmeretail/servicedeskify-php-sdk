<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use VmeRetail\ServiceDeskify\Auth\OAuthToken;
use VmeRetail\ServiceDeskify\Auth\TokenManager;
use VmeRetail\ServiceDeskify\Connectors\ServiceDeskifyConnector;
use VmeRetail\ServiceDeskify\Contracts\PreparesForRetry;
use VmeRetail\ServiceDeskify\Exceptions\ResponseExceptionMapper;
use VmeRetail\ServiceDeskify\Exceptions\ServiceDeskifyRequestException;

final readonly class ServiceDeskifyClient
{
    public function __construct(
        private string $baseUrl,
        private TokenManager $tokens,
    ) {}

    public function send(Request $request): Response
    {
        $token = $this->tokens->token();
        $response = $this->sendWithToken($request, $token);

        if ($response->status() === 401) {
            $this->tokens->forget();

            if ($request instanceof PreparesForRetry) {
                $request->prepareForRetry();
            }

            $response = $this->sendWithToken($request, $this->tokens->token(forcePasswordGrant: true));
        }

        ResponseExceptionMapper::throwIfFailed($response);

        return $response;
    }

    private function sendWithToken(Request $request, OAuthToken $token): Response
    {
        try {
            return (new ServiceDeskifyConnector(
                baseUrl: $this->baseUrl,
                accessToken: $token->accessToken,
                tokenType: $token->tokenType,
            ))->send($request);
        } catch (FatalRequestException|RequestException $exception) {
            throw ServiceDeskifyRequestException::transport($exception);
        }
    }
}
