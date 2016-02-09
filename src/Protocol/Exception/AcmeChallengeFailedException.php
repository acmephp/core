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
        $message = sprintf('Challenge check failed (response: %s)', json_encode($response));

        if (isset($response['challenges'])) {
            foreach ($response['challenges'] as $challenge) {
                if ('http-01' === $challenge['type'] && isset($challenge['error']['detail'])) {
                    $message = sprintf(
                        'Challenge check failed with message "%s"' . PHP_EOL .
                        'Full response:' . PHP_EOL . '%s',
                        $challenge['error']['detail'],
                        json_encode($response)
                    );
                }
            }
        }

        parent::__construct($message, 400);
    }
}
