<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Ssl;

use AcmePhp\Core\Ssl\Exception\GeneratingCsrFailedException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Generate CSR.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class CSRGenerator
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Generate CSR string from given parameters.
     *
     * @param string  $domain
     * @param KeyPair $domainKeyPair
     * @param CSR     $csr
     *
     * @return string
     */
    public function generateCSR($domain, KeyPair $domainKeyPair, CSR $csr)
    {
        $csrData = $csr->toArray();
        $csrData['commonName'] = $domain;
        $privateKey = $domainKeyPair->getPrivateKey();

        $this->logger->debug('Generating Certificate Signing Request ...', ['csrData' => $csrData]);

        $subjectAltName = $csr->getSubjectAlternativeNames();
        if (!in_array($domain, $subjectAltName)) {
            $subjectAltName[] = $domain;
        }

        if (1 < count($subjectAltName)) {
            $sslConfigTemplate = <<<'EOL'
[ req ]
distinguished_name = req_distinguished_name
req_extensions = v3_req
[ req_distinguished_name ]
[ v3_req ]
basicConstraints = CA:FALSE
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
subjectAltName = @req_subject_alt_name
[ req_subject_alt_name ]
%s
EOL;
            $sslConfigDomains = [];

            foreach (array_values($subjectAltName) as $index => $domain) {
                $sslConfigDomains[] = 'DNS.'.($index + 1).' = '.$domain;
            }

            $sslConfigContent = sprintf($sslConfigTemplate, implode("\n", $sslConfigDomains));
            $sslConfigFile = tempnam(sys_get_temp_dir(), 'acmephp_');

            try {
                file_put_contents($sslConfigFile, $sslConfigContent);
                $csrObject = openssl_csr_new(
                    $csrData,
                    $privateKey,
                    [
                        'digest_alg' => 'sha256',
                        'config'     => $sslConfigFile,
                    ]
                );
            } finally {
                unlink($sslConfigFile);
            }
        } else {
            $csrObject = openssl_csr_new(
                $csrData,
                $privateKey,
                ['digest_alg' => 'sha256']
            );
        }

        if (!$csrObject) {
            throw new GeneratingCsrFailedException(
                sprintf('OpenSSL CSR generation failed with error: %s', openssl_error_string())
            );
        }

        openssl_csr_export($csrObject, $csrExport);

        return $csrExport;
    }
}
