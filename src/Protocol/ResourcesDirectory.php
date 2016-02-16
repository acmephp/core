<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Protocol;

use Webmozart\Assert\Assert;

/**
 * Represent a ACME resources directory.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ResourcesDirectory
{
    const NEW_REGISTRATION = 'new-reg';
    const RECOVER_REGISTRATION = 'recover-reg';
    const NEW_AUTHORIZATION = 'new-authz';
    const NEW_CERTIFICATE = 'new-authz';
    const REVOKE_CERTIFICATE = 'new-authz';

    /**
     * @var array
     */
    private $resources;

    /**
     * @param array $resources
     */
    public function __construct(array $resources)
    {
        $this->resources = $resources;
    }

    /**
     * @return string[]
     */
    public static function getResourcesNames()
    {
        return [
            self::NEW_AUTHORIZATION,
            self::NEW_CERTIFICATE,
            self::NEW_REGISTRATION,
            self::REVOKE_CERTIFICATE,
        ];
    }

    /**
     * Find a resource URL.
     *
     * @param string $resource
     * @return string
     */
    public function getResourceUrl($resource)
    {
        Assert::oneOf($resource, self::getResourcesNames(), 'getResourceUrl() expected one of: %2$s. Got: %s');

        return isset($this->resources[$resource]) ? $this->resources[$resource] : null;
    }
}
