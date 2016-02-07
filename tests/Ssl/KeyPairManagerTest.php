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
    public function testGenerate()
    {
        $this->assertInstanceOf(KeyPair::class, KeyPairManager::generate());
    }

    public function testLoadValid()
    {
        $keyPair = KeyPairManager::load(
            $this->getFixturesDir() . '/account/public.pem',
            $this->getFixturesDir() . '/account/private.pem'
        );

        $this->assertInstanceOf(KeyPair::class, $keyPair);
    }

    /**
     * @expectedException \AcmePhp\Core\Ssl\Exception\LoadingSslKeyFailedException
     */
    public function testLoadInvalidPublic()
    {
        KeyPairManager::load(
            $this->getFixturesDir() . '/account/invalid-public.pem',
            $this->getFixturesDir() . '/account/private.pem'
        );
    }

    /**
     * @expectedException \AcmePhp\Core\Ssl\Exception\LoadingSslKeyFailedException
     */
    public function testLoadInvalidPrivate()
    {
        KeyPairManager::load(
            $this->getFixturesDir() . '/account/public.pem',
            $this->getFixturesDir() . '/account/invaid-private.pem'
        );
    }
}
