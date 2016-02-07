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

use AcmePhp\Core\AbstractAcmeClient;
use AcmePhp\Core\Ssl\KeyPair;
use AcmePhp\Core\Ssl\KeyPairManager;
use AcmePhp\Core\Tests\Mock\ArrayLogger;
use Psr\Log\LoggerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractAcmeClientTest extends UnitTest
{
    /**
     * @var ArrayLogger
     */
    protected $keyPair;

    /**
     * @var ArrayLogger
     */
    protected $logger;

    /**
     * @var AbstractAcmeClient
     */
    protected $client;

    /**
     * @param KeyPair $accountKeyPair
     * @param LoggerInterface|null $logger
     *
     * @return AbstractAcmeClient
     */
    abstract protected function createClient(KeyPair $accountKeyPair, LoggerInterface $logger = null);

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->keyPair = KeyPairManager::generate();
        $this->logger = new ArrayLogger();
        $this->client = $this->createClient($this->keyPair, $this->logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->keyPair = null;
        $this->logger = null;
        $this->client = null;
    }

    public function testRegisterAccountWithoutEmail()
    {
        $this->assertCount(0, $this->logger->getMessages());

        $response = $this->client->registerAccount();

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('key', $response);
        $this->assertArrayHasKey('agreement', $response);
        $this->assertArrayHasKey('initialIp', $response);
        $this->assertArrayHasKey('createdAt', $response);

        $this->assertCount(1, $this->logger->getMessages());
    }

    public function testRegisterAccountWithEmail()
    {
        $this->assertCount(0, $this->logger->getMessages());

        $response = $this->client->registerAccount('tgalopin@example.com');

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('key', $response);
        $this->assertArrayHasKey('agreement', $response);
        $this->assertArrayHasKey('initialIp', $response);
        $this->assertArrayHasKey('createdAt', $response);
        $this->assertArrayHasKey('contact', $response);
        $this->assertArrayHasKey(0, $response['contact']);
        $this->assertSame('mailto:tgalopin@example.com', $response['contact'][0]);

        $this->assertCount(1, $this->logger->getMessages());
    }

    public function testRequestChallengeWithoutRegistration()
    {
        $this->setExpectedExceptionRegExp(ClientException::class, '~403~');
        $this->client->requestChallenge('example.com');
    }

    public function testRequestChallengeWithRegistration()
    {
        $this->client->registerAccount();
        $challenge = $this->client->requestChallenge('example.com');

        $this->assertInstanceOf(Challenge::class, $challenge);
    }
}
