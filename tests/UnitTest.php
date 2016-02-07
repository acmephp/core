<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Tests;

use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class UnitTest extends \PHPUnit_Framework_TestCase
{
    protected function makeTempDir()
    {
        $basePath = $this->getTempDir().'/'.$this->getName();

        while (false === @mkdir($tempDir = $basePath.rand(10000, 99999), 0777, true)) {
            // Run until we are able to create a directory
        }

        return $tempDir;
    }

    protected function clearTempDir()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->getTempDir());
    }

    protected function getTempDir()
    {
        return str_replace('\\', '/', realpath(sys_get_temp_dir()));
    }
}
