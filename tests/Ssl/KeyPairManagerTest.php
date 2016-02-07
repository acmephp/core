<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Tests\Ssl;

use AcmePhp\Core\Ssl\KeyPair;
use AcmePhp\Core\Ssl\KeyPairManager;
use AcmePhp\Core\Tests\UnitTest;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class KeyPairManagerTest extends UnitTest
{
    public function testGenerateLoadValid()
    {
        $dir = $this->makeTempDir();

        $manager = new KeyPairManager($dir);

        $generated = $manager->generate('valid');
        $loaded = $manager->load('valid');

        $this->assertInstanceOf(KeyPair::class, $generated);
        $this->assertInstanceOf(KeyPair::class, $loaded);
        $this->assertSame($generated->getName(), $loaded->getName());
        $this->assertInternalType('resource', $generated->getPrivateKey());
        $this->assertInternalType('resource', $generated->getPublicKey());
        $this->assertInternalType('resource', $loaded->getPrivateKey());
        $this->assertInternalType('resource', $loaded->getPublicKey());

        $this->clearTempDir();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDirectoryInvalid()
    {
        new KeyPairManager(new \stdClass());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDirectoryNotReadable()
    {
        new KeyPairManager(__DIR__.'/invalid');
    }
}
