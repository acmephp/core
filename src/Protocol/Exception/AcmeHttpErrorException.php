<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Protocol\Exception;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;

/**
 * HTTP code status is error.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeHttpErrorException extends AcmeProtocolException
{
    public function __construct(RequestInterface $request, \Exception $exception)
    {
        $requestUrl = \GuzzleHttp\Psr7\uri_for($request->getUri());
        $code = 400;

        if ($exception instanceof GuzzleException) {
            $message = 'The ACME server returned an error HTTP status code ';
            $message .= 'during request to '.$requestUrl;

            if ($exception instanceof RequestException) {
                $code = $exception->getResponse()->getStatusCode();

                $body = \GuzzleHttp\Psr7\readline($exception->getResponse()->getBody());
                $json = @json_decode($body, true);

                if ($json && array_key_exists('detail', $json)) {
                    $message .= ' (detail: '.$code.' '.$json['detail'].')';
                } else {
                    $summary = RequestException::getResponseBodySummary($exception->getResponse());

                    if ($summary) {
                        $message .= ' (response: '.$code.' '.$summary.')';
                    }
                }
            }
        } else {
            $message = 'An error occured during request to '.$requestUrl;
            $message .= '(error: '.$exception->getMessage().')';
        }

        parent::__construct($message, $code, $exception);
    }
}
