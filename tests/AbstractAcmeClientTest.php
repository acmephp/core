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
use AcmePhp\Core\Protocol\Challenge;
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
     * @param KeyPair              $accountKeyPair
     * @param LoggerInterface|null $logger
     *
     * @return AbstractAcmeClient
     */
    abstract protected function createClient(KeyPair $accountKeyPair = null, LoggerInterface $logger = null);

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

    /*
     * Register account
     */

    /**
     * @expectedException \AcmePhp\Core\Exception\AccountKeyPairMissingException
     */
    public function testRegisterAccountWithoutAccount()
    {
        $this->createClient()->registerAccount();
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

    /**
     * @expectedException \AcmePhp\Core\Protocol\Exception\AcmeHttpErrorException
     */
    public function testRequestChallengeWithoutRegistration()
    {
        $this->client->requestChallenge('example.com');
    }

    /**
     * @expectedException \AcmePhp\Core\Protocol\Exception\AcmeHttpErrorException
     */
    public function testRegisterAccountTwice()
    {
        $this->client->registerAccount('tgalopin@example.com');
        $this->client->registerAccount('tgalopin@example.com');
    }

    /*
     * Request challenge
     */

    /**
     * @expectedException \AcmePhp\Core\Exception\AccountKeyPairMissingException
     */
    public function testRequestChallengeWithoutAccount()
    {
        $this->createClient()->requestChallenge('example.com');
    }

    public function testRequestChallengeWithRegistration()
    {
        $this->client->registerAccount();
        $challenge = $this->client->requestChallenge('example.com');

        $this->assertInstanceOf(Challenge::class, $challenge);
    }

    /**
     * @expectedException \AcmePhp\Core\Protocol\Exception\AcmeChallengeFailedException
     */
    public function testCheckChallengeFail()
    {
        $this->client->registerAccount();
        $challenge = $this->client->requestChallenge('example.com');
        $this->client->checkChallenge($challenge);
    }
}
