<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\ExtractPathParameters;

use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathMatcher::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[CoversClass(CannotProcessSpecification::class)]
class PathMatcherTest extends TestCase
{
    #[Test, TestDox('__toPHP() will return a string of evaluatable PHP code capable of constructing the same object')]
    public function toPHPReturnsPHPCodeToConstructSelf(): void
    {
        $sut = new PathMatcher('http://www.tea.social', '/biscuits/{quantity}');

        $phpCode = 'return ' . $sut->__toPHP() . ';';

        self::assertEquals($sut, eval($phpCode));
    }

    public static function dataSetsWithImbalancedBraces(): array
    {
        return [
            ['/pets/{id{name}}'],
            ['/pets/}{id}'],
        ];
    }

    #[DataProvider('dataSetsWithImbalancedBraces')]
    #[Test]
    public function throwsExceptionsForImbalancedBracesInAPIPaths(string $apiPath): void
    {
        self::expectExceptionObject(CannotProcessOpenAPI::invalidOpenAPI($apiPath));

        new PathMatcher('', $apiPath);
    }

    public static function dataSetsToMatch(): array
    {
        return [
            'path is identical, server missing, server path missing' => [
                'https://www.server.com/api',
                '/pets',
                '/pets',
                false,
            ],
            'path is identical, server missing' => [
                'https://www.server.com',
                '/pets',
                '/pets',
                true,
            ],
            'path is identical, default server' => [
                '',
                '/pets',
                '/pets',
                true,
            ],
            'path is identical, partial server match' => [
                'https://www.server.com/api',
                '/pets',
                '/api/pets',
                true,
            ],
            'path is identical, server is identical' => [
                'https://www.server.com/api',
                '/pets',
                'https://www.server.com/api/pets',
                true,
            ],
            'path matches pattern' => [
                'https://www.server.com/api',
                '/pets/{id}',
                '/api/pets/23',
                true,
            ],
            'path does not start with pattern' => [
                'https://www.server.com/api',
                '/pets/{id}',
                '///pets/23',
                false,
            ],
            'path does not end with pattern' => [
                'https://www.server.com/api',
                '/pets/{id}',
                '/pets/23/',
                false,
            ],
            'path does not match pattern' => [
                'https://www.server.com/api',
                '/pets/{id}',
                '/pet/23',
                false,
            ],
            'path matches complex pattern' => [
                'https://www.server.com/api',
                '/pets/{id}/photos/{photo_id}',
                '/api/pets/23/photos/5',
                true,
            ],
            'path does not match complex pattern' => [
                'https://www.server.com/api',
                '/pets/{id}/photos/{photo_id}',
                '/pets/23/37/5',
                false,
            ],
            'path matches petstore example' => [
                'http://swagger.petstore.io/v1',
                '/pets',
                '/v1/pets',
                true,
            ],
        ];
    }

    #[DataProvider('dataSetsToMatch')]
    #[Test]
    public function matchesTest(string $serverUrl, string $apiPath, string $requestPath, bool $expected): void
    {
        $sut = new PathMatcher($serverUrl, $apiPath);

        $actual = $sut->matches($requestPath);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function throwsExceptionGettingParamsForNonMatchingPaths(): void
    {
        $sut = new PathMatcher('https://www.server.com', '/pets/{id}');

        self::expectExceptionObject(CannotProcessSpecification::mismatchedPath('#^/pets/(?<id>[^/]+)$#', '/hats/23',));

        $sut->getPathParams('/hats/23');
    }

    public static function dataSetsToGetPathParams(): array
    {
        return [
            [
                '/pets/{id}',
                '/pets/23',
                ['id' => '23'],
            ],
            [
                '/pets/{id}/{name}',
                '/pets/23/Ben',
                ['id' => '23', 'name' => 'Ben'],
            ],
            [
                '/pets/{id}/{name}',
                '/pets/23/Ben?page=2&count=10',
                ['id' => '23', 'name' => 'Ben'],
            ],
            [
                '/pets/{id}/photos/{photo_id}',
                '/pets/1/photos/5',
                ['id' => '1', 'photo_id' => '5'],
            ],
        ];
    }

    #[DataProvider('dataSetsToGetPathParams')]
    #[Test]
    public function getPathParamsTest(string $apiUrl, string $requestUrl, array $expected): void
    {
        $sut = new PathMatcher('https://www.server.com', $apiUrl);

        $actual = $sut->getPathParams($requestUrl);

        self::assertSame($expected, $actual);
    }
}
