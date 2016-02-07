<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * The ACME server returned an invalid response.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeInvalidResponseException extends \RuntimeException
{
    /**
     * @var string
     */
    private $requestMethod;

    /**
     * @var string
     */
    private $requestUrl;

    /**
     * @var array
     */
    private $requestPayload;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @param string            $requestMethod
     * @param string            $requestUrl
     * @param array             $requestPayload
     * @param ResponseInterface $response
     */
    public function __construct($requestMethod, $requestUrl, array $requestPayload, ResponseInterface $response)
    {
        parent::__construct(sprintf(
            'The ACME server did not return a valid JSON response on request "%s %s" (payload: %s)',
            $requestMethod,
            $requestUrl,
            json_encode($requestPayload)
        ));

        $this->requestMethod = $requestMethod;
        $this->requestUrl = $requestUrl;
        $this->requestPayload = $requestPayload;
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * @return array
     */
    public function getRequestPayload()
    {
        return $this->requestPayload;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
