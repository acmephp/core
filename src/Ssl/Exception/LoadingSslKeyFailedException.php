<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Ssl\Exception;

/**
 * Loading of a SSL key failed.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class LoadingSslKeyFailedException extends AcmeSslException
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $keyFile;

    /**
     * @param string $type
     * @param string $keyFile
     * @param string $message
     */
    public function __construct($type, $keyFile, $message)
    {
        parent::__construct(sprintf(
            'Reading of the %s key file "%s" failed with message: %s',
            $type,
            $keyFile,
            $message
        ));

        $this->type = $type;
        $this->keyFile = $keyFile;
    }
}
