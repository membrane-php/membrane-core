<?php
//
//declare(strict_types=1);
//
//namespace OpenAPI\Router;
//
//use Membrane\OpenAPI\Exception\CannotProcessRequest;
//use Membrane\OpenAPI\Router\Router;
//use Membrane\OpenAPI\Router\ValueObjects\Operation;
//use Membrane\OpenAPI\Router\ValueObjects\OperationCollection;
//use PHPUnit\Framework\TestCase;
//
///**
// * @covers \Membrane\OpenAPI\Router\Router
// * @covers \Membrane\OpenAPI\Exception\CannotProcessRequest
// * @uses   \Membrane\OpenAPI\Router\ValueObjects\OperationCollection
// * @uses   \Membrane\OpenAPI\Router\ValueObjects\PathCollection
// * @uses   \Membrane\OpenAPI\Router\ValueObjects\ServerCollection
// */
//class RouterTest extends TestCase
//{
//    public function unsuccessfulRouteProvider(): array
//    {
//        $petStoreOperationCollection = new OperationCollection(
//            new Operation(['http://petstore.swagger.io/api'], '/pets', 'get', 'findPets'),
//            new Operation(['http://petstore.swagger.io/api'], '/pets', 'post', 'addPet'),
//            new Operation(['http://petstore.swagger.io/api'], '/pets/{id}', 'get', 'find pet by id'),
//            new Operation(['http://petstore.swagger.io/api'], '/pets/{id}', 'delete', 'deletePet'),
//        );
//
//        return [
//            'petstore-expanded: incorrect server url' => [
//                CannotProcessRequest::serverNotFound('https://hatshop.dapper.net/api/pets'),
//                'https://hatshop.dapper.net/api/pets',
//                'get',
//                $petStoreOperationCollection,
//            ],
//            'petstore-expanded: correct server url but incorrect path' => [
//                CannotProcessRequest::pathNotFound('/hats'),
//                'http://petstore.swagger.io/api/hats',
//                'get',
//                $petStoreOperationCollection,
//            ],
//            'petstore-expanded: correct url but incorrect method' => [
//                CannotProcessRequest::methodNotFound('delete'),
//                'http://petstore.swagger.io/api/pets',
//                'delete',
//                $petStoreOperationCollection,
//            ],
//        ];
//    }
//
//    /**
//     * @test
//     * @dataProvider unsuccessfulRouteProvider
//     */
//    public function unsuccessfulRouteTest(
//        CannotProcessRequest $expected,
//        string $path,
//        string $method,
//        OperationCollection $operationCollection
//    ): void {
//        $sut = new Router($operationCollection);
//
//        self::expectExceptionObject($expected);
//
//        $sut->route($path, $method);
//    }
//
//    public function successfulRouteProvider(): array
//    {
//        $petStoreOperationCollection = new OperationCollection(
//            new Operation(['http://petstore.swagger.io/api'], '/pets', 'get', 'findPets'),
//            new Operation(['http://petstore.swagger.io/api'], '/pets', 'post', 'addPet'),
//            new Operation(['http://petstore.swagger.io/api'], '/pets/{id}', 'get', 'find pet by id'),
//            new Operation(['http://petstore.swagger.io/api'], '/pets/{id}', 'delete', 'deletePet'),
//        );
//
//        return [
//            'petstore: /pets path, get method' => [
//                'findPets',
//                'http://petstore.swagger.io/api/pets',
//                'get',
//                $petStoreOperationCollection,
//            ],
//            'petstore: /pets/{id} path, get method' => [
//                'find pet by id',
//                'http://petstore.swagger.io/api/pets/1',
//                'get',
//                $petStoreOperationCollection,
//            ],
//            'petstore: /pets/{id} path, delete method' => [
//                'deletePet',
//                'http://petstore.swagger.io/api/pets/1',
//                'delete',
//                $petStoreOperationCollection,
//            ],
//        ];
//    }
//
//    /**
//     * @test
//     * @dataProvider successfulRouteProvider
//     */
//    public function successfulRouteTest(
//        string $expected,
//        string $path,
//        string $method,
//        OperationCollection $operationCollection
//    ): void {
//        $sut = new Router($operationCollection);
//
//        $actual = $sut->route($path, $method);
//
//        self::assertSame($expected, $actual);
//    }
//}
