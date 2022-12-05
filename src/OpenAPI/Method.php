<?php

declare(strict_types=1);

namespace Membrane\OpenAPI;

enum Method: string
{
    case GET = 'get';
    case POST = 'post';
    case PUT = 'put';
    case DELETE = 'delete';
}
