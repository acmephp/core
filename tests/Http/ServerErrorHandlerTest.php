<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Http;

use AcmePhp\Core\Http\ServerErrorHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class ServerErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function getErrorTypes()
    {
        return [
            ['badCSR', 'BadCsrServerException'],
            ['badNonce', 'BadNonceServerException'],
            ['connection', 'ConnectionServerException'],
            ['serverInternal', 'InternalServerException'],
            ['invalidEmail', 'InvalidEmailServerException'],
            ['malformed', 'MalformedServerException'],
            ['rateLimited', 'RateLimitedServerException'],
            ['tls', 'TlsServerException'],
            ['unauthorized', 'UnauthorizedServerException'],
            ['unknownHost', 'UnknownHostServerException'],
        ];
    }

    /**
     * @dataProvider getErrorTypes
     */
    public function testAcmeExceptionThrown($type, $exceptionClass)
    {
        $errorHandler = new ServerErrorHandler();

        $response = new Response(500, [], json_encode([
            'type'   => 'urn:acme:error:'.$type,
            'detail' => $exceptionClass.'Detail',
        ]));

        $exception = $errorHandler->createAcmeExceptionForResponse(new Request('GET', '/foo/bar'), $response);

        $this->assertInstanceOf('AcmePhp\\Core\\Exception\\Server\\'.$exceptionClass, $exception);
        $this->assertContains($type, $exception->getMessage());
        $this->assertContains($exceptionClass.'Detail', $exception->getMessage());
        $this->assertContains('/foo/bar', $exception->getMessage());
    }

    public function testDefaultExceptionThrownWithInvalidJson()
    {
        $errorHandler = new ServerErrorHandler();

        $exception = $errorHandler->createAcmeExceptionForResponse(
            new Request('GET', '/foo/bar'),
            new Response(500, [], 'Invalid JSON')
        );

        $this->assertInstanceOf('AcmePhp\\Core\\Exception\\AcmeCoreServerException', $exception);
        $this->assertContains('non-ACME', $exception->getMessage());
        $this->assertContains('/foo/bar', $exception->getMessage());
    }

    public function testDefaultExceptionThrownNonAcmeJson()
    {
        $errorHandler = new ServerErrorHandler();

        $exception = $errorHandler->createAcmeExceptionForResponse(
            new Request('GET', '/foo/bar'),
            new Response(500, [], json_encode(['not' => 'acme']))
        );

        $this->assertInstanceOf('AcmePhp\\Core\\Exception\\AcmeCoreServerException', $exception);
        $this->assertContains('non-ACME', $exception->getMessage());
        $this->assertContains('/foo/bar', $exception->getMessage());
    }
}
