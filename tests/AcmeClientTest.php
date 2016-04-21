<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core;

use AcmePhp\Core\AcmeClient;
use AcmePhp\Core\AcmeClientInterface;
use AcmePhp\Core\Http\Base64SafeEncoder;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Http\ServerErrorHandler;
use AcmePhp\Ssl\Generator\KeyPairGenerator;
use AcmePhp\Ssl\Parser\KeyParser;
use AcmePhp\Ssl\Signer\DataSigner;
use GuzzleHttp\Client;

class AcmeClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AcmeClientInterface
     */
    private $client;

    public function setUp()
    {
        $secureHttpClient = new SecureHttpClient(
            (new KeyPairGenerator())->generateKeyPair(),
            new Client(),
            new Base64SafeEncoder(),
            new KeyParser(),
            new DataSigner(),
            new ServerErrorHandler()
        );

        $this->client = new AcmeClient($secureHttpClient, 'http://127.0.0.1:4000/directory');
    }

    /**
     * @expectedException \AcmePhp\Core\Exception\Server\MalformedServerException
     */
    public function testDoubleRegisterAccountFail()
    {
        $this->client->registerAccount();
        $this->client->registerAccount();
    }

    /**
     * @expectedException \AcmePhp\Core\Exception\Server\MalformedServerException
     */
    public function testInvalidAgreement()
    {
        $this->client->registerAccount('http://invalid.com');
        $this->client->requestChallenge('example.org');
    }

    public function testFullProcess()
    {
        $data = $this->client->registerAccount('http://boulder:4000/terms/v1');

        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('key', $data);
        $this->assertArrayHasKey('initialIp', $data);
        $this->assertArrayHasKey('createdAt', $data);

        $challenge = $this->client->requestChallenge('acmephp.com');

        $this->assertInstanceOf('AcmePhp\\Core\\Protocol\\Challenge', $challenge);
        $this->assertEquals('acmephp.com', $challenge->getDomain());
        $this->assertContains('http://127.0.0.1:4000/acme/challenge', $challenge->getUrl());
        $this->assertContains('http://127.0.0.1:4000/acme/authz', $challenge->getLocation());
    }
}
