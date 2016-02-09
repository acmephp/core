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
 * The ACME challenge failed.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AcmeChallengeFailedException extends AcmeProtocolException
{
    public function __construct(array $response)
    {
        parent::__construct(sprintf('Check challenge failed (body: %s)', json_encode($response)));
    }
}
