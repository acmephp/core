<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Protocol;

use AcmePhp\Core\Protocol\Exception\AcmeHttpErrorException;
use AcmePhp\Core\Protocol\Exception\AcmeInvalidResponseException;
use AcmePhp\Core\Ssl\KeyPair;
use AcmePhp\Core\Util\Base64UrlSafeEncoder;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle HTTP client wrapper to send requests signed with the account KeyPair.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class SecureHttpClient
{
    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var KeyPair
     */
    private $accountKeyPair;

    /**
     * @var ResponseInterface
     */
    private $lastResponse;

    /**
     * @param string  $authority
     * @param KeyPair $accountKeyPair
     */
    public function __construct($authority, KeyPair $accountKeyPair)
    {
        $this->accountKeyPair = $accountKeyPair;
        $this->httpClient = new Client([
            'base_uri' => $authority,
        ]);
    }

    /**
     * Send a request encoded in the format defined by the ACME protocol.
     *
     * @param string $method
     * @param string $endpoint
     * @param array  $payload
     *
     * @return array If the server returns an invalid response.
     */
    public function request($method, $endpoint, array $payload)
    {
        $privateKey = $this->accountKeyPair->getPrivateKey();
        $details = openssl_pkey_get_details($privateKey);

        $header = [
            'alg' => 'RS256',
            'jwk' => [
                'kty' => 'RSA',
                'n'   => Base64UrlSafeEncoder::encode($details['rsa']['n']),
                'e'   => Base64UrlSafeEncoder::encode($details['rsa']['e']),
            ],
        ];

        if (!$this->lastResponse) {
            $this->lastResponse = $this->doRequest('GET', '/directory');
        }

        $protected = $header;
        $protected['nonce'] = $this->lastResponse->getHeaderLine('Replay-Nonce');

        $payload = Base64UrlSafeEncoder::encode(str_replace('\\/', '/', json_encode($payload)));
        $protected = Base64UrlSafeEncoder::encode(json_encode($protected));

        openssl_sign($protected.'.'.$payload, $signature, $privateKey, 'SHA256');
        $signature = Base64UrlSafeEncoder::encode($signature);

        $payload = [
            'header'    => $header,
            'protected' => $protected,
            'payload'   => $payload,
            'signature' => $signature,
        ];

        $this->lastResponse = $this->doRequest($method, $endpoint, $payload);

        $data = json_decode(\GuzzleHttp\Psr7\readline($this->lastResponse->getBody()), true);

        if (!$data) {
            throw new AcmeInvalidResponseException($method, $endpoint, $payload, $this->lastResponse);
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getLastLocation()
    {
        return $this->lastResponse->getHeaderLine('Location');
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array  $data
     *
     * @return ResponseInterface
     *
     * @throws AcmeHttpErrorException When the ACME server returns an error HTTP status code.
     */
    private function doRequest($method, $endpoint, array $data = null)
    {
        $request = new Request($method, $endpoint);
        $request = $request->withHeader('Accept', 'application/json');

        if ('POST' === $method && is_array($data)) {
            $request = $request->withHeader('Content-Type', 'application/json');
            $request = $request->withBody(\GuzzleHttp\Psr7\stream_for(json_encode($data)));
        }

        try {
            $response = $this->httpClient->send($request);
        } catch (\Exception $exception) {
            throw new AcmeHttpErrorException($request, $exception);
        }

        return $response;
    }
}
