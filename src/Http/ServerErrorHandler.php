<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Http;

use AcmePhp\Core\Exception\AcmeCoreServerException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Create appropriate exception for given server response.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ServerErrorHandler
{
    private static $exceptions = [
        'badCSR'         => 'BadCsrServerException',
        'badNonce'       => 'BadNonceServerException',
        'connection'     => 'ConnectionServerException',
        'serverInternal' => 'InternalServerException',
        'invalidEmail'   => 'InvalidEmailServerException',
        'malformed'      => 'MalformedServerException',
        'rateLimited'    => 'RateLimitedServerException',
        'tls'            => 'TlsServerException',
        'unauthorized'   => 'UnauthorizedServerException',
        'unknownHost'    => 'UnknownHostServerException',
    ];

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param \Exception|null   $previous
     *
     * @return AcmeCoreServerException
     */
    public function createAcmeExceptionForResponse(
        RequestInterface $request,
        ResponseInterface $response,
        \Exception $previous = null
    ) {
        $body = \GuzzleHttp\Psr7\copy_to_string($response->getBody());
        $data = @json_decode($body, true);

        if (!$data || !isset($data['type'], $data['detail'])) {
            // Not JSON: not an ACME error response
            return $this->createDefaultExceptionForResponse($request, $response, $previous);
        }

        // Remove "urn:acme:error:" prefix
        $type = substr($data['type'], 15);

        if (!isset(self::$exceptions[$type])) {
            // Unknown type: not an ACME error response
            return $this->createDefaultExceptionForResponse($request, $response, $previous);
        }

        $exceptionClass = 'AcmePhp\\Core\\Exception\\Server\\'.self::$exceptions[$type];

        return new $exceptionClass(
            $request,
            sprintf('%s (on request "%s %s")', $data['detail'], $request->getMethod(), $request->getUri()),
            $previous
        );
    }

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param \Exception|null   $previous
     *
     * @return AcmeCoreServerException
     */
    private function createDefaultExceptionForResponse(
        RequestInterface $request,
        ResponseInterface $response,
        \Exception $previous = null
    ) {
        return new AcmeCoreServerException(
            $request,
            sprintf(
                'A non-ACME %s HTTP error occured on request "%s %s" (response body: "%s")',
                $response->getStatusCode(),
                $request->getMethod(),
                $request->getUri(),
                RequestException::getResponseBodySummary($response)
            ),
            $previous
        );
    }
}
