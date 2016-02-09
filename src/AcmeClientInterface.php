<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core;

use AcmePhp\Core\Protocol\Challenge;
use AcmePhp\Core\Protocol\Exception\AcmeChallengeFailedException;
use AcmePhp\Core\Protocol\Exception\AcmeChallengeNotSupportedException;
use AcmePhp\Core\Protocol\Exception\AcmeChallengeTimedOutException;
use AcmePhp\Core\Protocol\Exception\AcmeHttpErrorException;
use AcmePhp\Core\Ssl\Certificate;
use AcmePhp\Core\Ssl\KeyPair;

/**
 * ACME protocol client.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface AcmeClientInterface
{
    /**
     * Specify which KeyPair the client should use as the account for requests
     * with the Certificate Authority.
     *
     * If no account KeyPair is specified, the other methods will fail.
     *
     * @param KeyPair $keyPair The KeyPair to use as an account.
     *
     * @return void
     */
    public function useAccountKeyPair(KeyPair $keyPair);

    /**
     * Register the local account KeyPair in the Certificate Authority.
     *
     * @param string|null $email An optionnal e-mail to associate with the
     *                           account.
     *
     * @return array The Certificate Authority response decoded from JSON into
     *               an array.
     *
     * @throws AcmeHttpErrorException When the ACME server returns an error HTTP status code.
     */
    public function registerAccount($email = null);

    /**
     * Request challenge data for a given domain.
     *
     * A Challenge is an association between a URI, a token and a payload.
     * The Certificate Authority will create this challenge data and
     * you will then have to expose the payload for the verification
     * (see requestCheck).
     *
     * @param string $domain The domain to challenge.
     *
     * @return Challenge The data returned by the Certificate Authority.
     *
     * @throws AcmeChallengeNotSupportedException When the HTTP challenge is not supported by the server.
     * @throws AcmeHttpErrorException When the ACME server returns an error HTTP status code.
     */
    public function requestChallenge($domain);

    /**
     * Ask the Certificate Authority to check given challenge data.
     *
     * This check will generally consists of requesting over HTTP the domain
     * at a specific URL. This URL should return the raw payload generated
     * by requestChallenge.
     *
     * WARNING : This method SHOULD NOT BE USED in a web action. It will
     * wait for the Certificate Authority to validate the check and this
     * operation could be long.
     *
     * @param Challenge $challenge The challenge data to check.
     * @param int       $timeout   The timeout period.
     *
     * @throws AcmeChallengeFailedException When the challenge failed.
     * @throws AcmeChallengeTimedOutException When the challenge timed out.
     * @throws AcmeHttpErrorException When the ACME server returns an error HTTP status code.
     */
    public function checkChallenge(Challenge $challenge, $timeout = 180);

    /**
     * Request a certificate for the given domain.
     *
     * This method should be called only if the previous check challenge has
     * been successful.
     *
     * WARNING : This method SHOULD NOT BE USED in a web action. It will
     * wait for the Certificate Authority to validate the certificate and
     * this operation could be long.
     *
     * @param string  $domain        The domain to request a certificate for.
     * @param KeyPair $domainKeyPair The domain SSL KeyPair to use (for renewal).
     * @param int     $timeout       The timeout period.
     *
     * @return Certificate The certificate data to save somewhere you want.
     *
     * @throws AcmeHttpErrorException When the ACME server returns an error HTTP status code.
     */
    public function requestCertificate($domain, KeyPair $domainKeyPair, $timeout = 180);
}
