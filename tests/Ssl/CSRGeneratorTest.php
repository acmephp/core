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

use AcmePhp\Core\Ssl\CSR;
use AcmePhp\Core\Ssl\CSRGenerator;
use AcmePhp\Core\Ssl\KeyPairManager;
use AcmePhp\Core\Tests\UnitTest;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class CSRGeneratorTest extends UnitTest
{
    public function testGenerateCSRWithoutAltName()
    {
        $domain = 'company.com';
        $keyPair = KeyPairManager::generate();
        $csr = new CSR('FR', 'France', 'Paris', 'Company', 'IT', 'john@doe.com');

        $generator = new CSRGenerator();

        $resultString = $generator->generateCSR($domain, $keyPair, $csr);
        $result = $this->readCsr($resultString);

        $this->assertContains('CN=company.com', $result);
        $this->assertNotContains('DNS:company.com', $result);
    }

    public function testGenerateCSRWithAltName()
    {
        $domain = 'company.com';
        $keyPair = KeyPairManager::generate();
        $csr = new CSR('FR', 'France', 'Paris', 'Company', 'IT', 'john@doe.com', ['www.company.com']);

        $generator = new CSRGenerator();

        $resultString = $generator->generateCSR($domain, $keyPair, $csr);
        $result = $this->readCsr($resultString);

        $this->assertContains('CN=company.com', $result);
        $this->assertContains('DNS:company.com', $result);
        $this->assertContains('DNS:www.company.com', $result);
    }

    private function readCsr($csrString)
    {
        $fileName = tempnam(sys_get_temp_dir(), 'acme_test');

        try {
            file_put_contents($fileName, $csrString);
            exec('openssl req -in '.$fileName.' -text -noout', $output);

            return implode(PHP_EOL, $output);
        } finally {
            unlink($fileName);
        }
    }
}
