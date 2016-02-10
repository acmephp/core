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

/**
 * The ACME certificate request failed.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeCertificateRequestFailedException extends AcmeProtocolException
{
    public function __construct(array $response)
    {
        $message = sprintf('Certificate request failed (response: %s)', json_encode($response));

        if (isset($response['error']['detail'])) {
            $message = sprintf(
                'Challenge check failed with message "%s"' . PHP_EOL .
                'Full response:' . PHP_EOL . '%s',
                $response['error']['detail'],
                json_encode($response)
            );
        }

        parent::__construct($message, 400);
    }
}
