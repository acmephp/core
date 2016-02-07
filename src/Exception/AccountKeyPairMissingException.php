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

/**
 * Account KeyPair is missing.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class AccountKeyPairMissingException extends AcmePhpException
{
    public function __construct()
    {
        parent::__construct(
            'ACME clients require an account KeyPair to dialog with the ACME server. '.
            'Please provide one either in the constructor of your client or using the '.
            'method AcmeClientInterface::useAccountKeyPair($keyPair).'
        );
    }
}
