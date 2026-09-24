<?php

namespace Benchero\Core;

/**
 * Global registry for custom domain context during the request.
 */
class CustomDomainContext
{
    private static ?array $activeOrg = null;
    private static ?string $activeDomain = null;

    public static function set(array|string|null $orgOrDomain, array|string|null $domainOrOrg = null): void
    {
        if (is_array($orgOrDomain)) {
            self::$activeOrg = $orgOrDomain;
            self::$activeDomain = is_string($domainOrOrg) ? $domainOrOrg : null;
        } elseif (is_string($orgOrDomain)) {
            self::$activeDomain = $orgOrDomain;
            self::$activeOrg = is_array($domainOrOrg) ? $domainOrOrg : null;
        } else {
            self::$activeOrg = null;
            self::$activeDomain = null;
        }
    }

    public static function getOrg(): ?array
    {
        return self::$activeOrg;
    }

    public static function getDomain(): ?string
    {
        return self::$activeDomain;
    }

    public static function isActive(): bool
    {
        return self::$activeDomain !== null && self::$activeOrg !== null;
    }

    public static function reset(): void
    {
        self::$activeOrg = null;
        self::$activeDomain = null;
    }

    public static function clear(): void
    {
        self::reset();
    }
}
