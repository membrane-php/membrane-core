<?php

declare(strict_types=1);

namespace OpenAPI\ExtractPathParameters;

use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\ExtractPathParameters\PathParameterExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathParameterExtractor::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
class PathParameterExtractorTest extends TestCase
{
    #[Test, TestDox('__toPHP() will return a string of evaluatable PHP code capable of constructing the same object')]
    public function toPHPReturnsPHPCodeToConstructSelf(): void
    {
        $sut = new PathParameterExtractor('/biscuits/{quantity}');

        $phpCode = 'return ' . $sut->__toPHP() . ';';

        self::assertEquals($sut, eval($phpCode));
    }

    public function providePathsWithImbalancedBraces(): array
    {
        return [
            ['/pets/{id{name}}'],
            ['/pets/}{id}'],
        ];
    }

    #[Test, TestDox('throws Exceptions for paths with unbalanced or nested braces')]
    #[DataProvider('providePathsWithImbalancedBraces')]
    public function throwsExceptionsForImbalancedBracesInAPIPaths(string $apiPath): void
    {
        self::expectExceptionObject(CannotProcessOpenAPI::invalidPath($apiPath));

        new PathParameterExtractor($apiPath);
    }

    public function providePathsAndParameters(): array
    {
        return [
            [
                '/pets/{id}',
                '/pets/23',
                ['id' => '23'],
            ],
            [
                '/pets/{id}',
                'http://www.server.com/pets/23',
                ['id' => '23'],
            ],
            [
                '/pets/{id}/{name}',
                '/pets/23/Ben',
                ['id' => '23', 'name' => 'Ben'],
            ],
            [
                '/pets/{id}/{name}',
                'http://petstore.swagger.io/api/pets/23/Ben',
                ['id' => '23', 'name' => 'Ben'],
            ],
            [
                '/pets/{id}/{name}',
                '/pets/23/Ben?page=2&count=10',
                ['id' => '23', 'name' => 'Ben'],
            ],
            [
                '/pets/{id}/{name}',
                'https://www.hatstore.social/pets/23/Ben?page=2&count=10',
                ['id' => '23', 'name' => 'Ben'],
            ],
            [
                '/pets/{id}/photos/{photo_id}',
                '/pets/1/photos/5',
                ['id' => 1, 'photo_id' => 5],
            ],
            [
                '/pets/{id}/photos/{photo_id}',
                'www.ham.sandwich.io/pets/1/photos/5',
                ['id' => 1, 'photo_id' => 5],
            ],
        ];
    }

    #[Test, TestDox('getPathParameters() will extract an array of path parrameters from a given url')]
    #[DataProvider('providePathsAndParameters')]
    public function getPathParamsTest(string $apiUrl, string $requestUrl, array $expected): void
    {
        $sut = new PathParameterExtractor($apiUrl);

        $actual = $sut->getPathParams($requestUrl);

        self::assertEquals($expected, $actual);
    }
}
