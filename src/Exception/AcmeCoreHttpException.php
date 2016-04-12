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

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeCoreHttpException extends AcmeCoreException
{
    public function __construct(RequestInterface $request, \Exception $exception)
    {
        $requestUrl = \GuzzleHttp\Psr7\uri_for($request->getUri());
        $code = 400;

        if ($exception instanceof RequestException && $exception->getResponse() instanceof ResponseInterface) {
            $message = 'The ACME server returned the error HTTP status code ';
            $message .= 'during request to '.$requestUrl;

            $code = $exception->getResponse()->getStatusCode();

            $body = \GuzzleHttp\Psr7\copy_to_string($exception->getResponse()->getBody());
            $json = @json_decode($body, true);

            if ($json && array_key_exists('detail', $json)) {
                $message .= ' (detail: '.$code.' '.$json['detail'].')';
            } else {
                $summary = RequestException::getResponseBodySummary($exception->getResponse());

                if ($summary) {
                    $message .= ' (response: '.$code.' '.$summary.')';
                }
            }
        } else {
            $message = 'An error occured during request to '.$requestUrl;
            $message .= ' (error: '.$exception->getMessage().')';
        }

        parent::__construct($message, $code, $exception);
    }
}
