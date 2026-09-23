<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\OpenAPI;

use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;

abstract class Provider
{
    private static MembraneReader $membraneReader;

    protected static function reader(): MembraneReader
    {
        return self::$membraneReader ??= new MembraneReader(
            supportedVersions: OpenAPIVersion::cases(),
        );
    }
}
