<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Exception\Protocol;

/**
 * The ACME certificate request timed out.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeCertificateRequestTimedOutException extends AcmeProtocolException
{
    public function __construct(array $response)
    {
        parent::__construct(sprintf('Certificate request timed out (body: %s)', json_encode($response)));
    }
}
