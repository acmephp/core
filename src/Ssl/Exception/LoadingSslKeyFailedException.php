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

use AcmePhp\Core\Exception\AcmePhpException;

/**
 * Loading of a SSL key failed.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class LoadingSslKeyFailedException extends AcmePhpException
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $file;

    /**
     * @param string $type
     * @param string $file
     * @param string $message
     */
    public function __construct($type, $file, $message)
    {
        parent::__construct(sprintf(
            'Reading of the %s key file "%s" failed with message: %s',
            $type,
            $file,
            $message
        ));

        $this->type = $type;
        $this->file = $file;
    }
}
