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

use Webmozart\Assert\Assert;

/**
 * Represent a Certificate Signing Request.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class CSR
{
    /**
     * @var string
     */
    private $countryName;

    /**
     * @var string
     */
    private $stateOrProvinceName;

    /**
     * @var string
     */
    private $localityName;

    /**
     * @var string
     */
    private $organizationName;

    /**
     * @var string
     */
    private $organizationalUnitName;

    /**
     * @var string
     */
    private $emailAddress;

    /**
     * @var array
     */
    private $subjectAlternativeNames;

    /**
     * @param string $countryName
     * @param string $stateOrProvinceName
     * @param string $localityName
     * @param string $organizationName
     * @param string $organizationalUnitName
     * @param string $emailAddress
     * @param string $subjectAlternativeNames
     */
    public function __construct(
        $countryName,
        $stateOrProvinceName,
        $localityName,
        $organizationName,
        $organizationalUnitName,
        $emailAddress,
        $subjectAlternativeNames = []
    ) {
        Assert::string($countryName, 'CSR::$countryName expected a string. Got: %s');
        Assert::string($stateOrProvinceName, 'CSR::$stateOrProvinceName expected a string. Got: %s');
        Assert::string($localityName, 'CSR::$localityName expected a string. Got: %s');
        Assert::string($organizationName, 'CSR::$organizationName expected a string. Got: %s');
        Assert::string($organizationalUnitName, 'CSR::$organizationalUnitName expected a string. Got: %s');
        Assert::string($emailAddress, 'CSR::$emailAddress expected a string. Got: %s');
        Assert::isArray($subjectAlternativeNames, 'CSR::$subjectAlternativeNames expected an array. Got: %s');
        Assert::allString($subjectAlternativeNames, 'CSR::$subjectAlternativeNames expected an array of string. Got: %s');

        $this->countryName = $countryName;
        $this->stateOrProvinceName = $stateOrProvinceName;
        $this->localityName = $localityName;
        $this->organizationName = $organizationName;
        $this->organizationalUnitName = $organizationalUnitName;
        $this->emailAddress = $emailAddress;
        $this->subjectAlternativeNames = array_unique($subjectAlternativeNames);
    }

    public function toArray()
    {
        return [
            'countryName'            => $this->countryName,
            'stateOrProvinceName'    => $this->stateOrProvinceName,
            'localityName'           => $this->localityName,
            'organizationName'       => $this->organizationName,
            'organizationalUnitName' => $this->organizationalUnitName,
            'emailAddress'           => $this->emailAddress,
        ];
    }

    /**
     * @return string
     */
    public function getCountryName()
    {
        return $this->countryName;
    }

    /**
     * @return string
     */
    public function getStateOrProvinceName()
    {
        return $this->stateOrProvinceName;
    }

    /**
     * @return string
     */
    public function getLocalityName()
    {
        return $this->localityName;
    }

    /**
     * @return string
     */
    public function getOrganizationName()
    {
        return $this->organizationName;
    }

    /**
     * @return string
     */
    public function getOrganizationalUnitName()
    {
        return $this->organizationalUnitName;
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @return array
     */
    public function getSubjectAlternativeNames()
    {
        return $this->subjectAlternativeNames;
    }
}
