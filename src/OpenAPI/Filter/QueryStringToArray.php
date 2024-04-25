<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Filter;

use Membrane\Filter;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class QueryStringToArray implements Filter
{
    /**
     * @param array<string, array{
     *     style: string,
     *     explode: bool,
     * }> $parameters
     */
    public function __construct(
        private readonly array $parameters
    ) {
    }

    public function __toString(): string
    {
        return 'convert query string to an array of query parameters';
    }

    public function __toPHP(): string
    {
        $encodedParameters = [];
        foreach ($this->parameters as $name => $parameter) {
            $encodedParameters[] = sprintf(
                '"%s" => ["style" => "%s", "explode" => %s]',
                $name,
                $parameter['style'],
                $parameter['explode'] ? 'true' : 'false',
            );
        }

        return sprintf('new %s([%s])', self::class, implode(',', $encodedParameters));
    }

    public function filter(mixed $value): Result
    {

        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('String expected, %s provided', [gettype($value)])
                )
            );
        }

        $tokens = array_filter(explode('&', rawurldecode($value)), fn($p) => $p !== '');


        $index = 0;
        $resolvedTokens = [];
        $unresolvedTokens = [];
        while (!empty($tokens)) {
            $token = $tokens[$index];
            unset($tokens[$index++]);


            $name = strtok($token, '[=');

            if (
                isset($this->parameters[$name]) &&
                $this->matchesStyle($token, $this->parameters[$name]['style'])
            ) {
                $resolvedTokens[$name][] = $token;
            } else {
                $unresolvedTokens[] = $token;
            }
        }

        $unresolvedParameters = array_filter(
            $this->parameters,
            fn($p) => !isset($resolvedTokens[$p]),
            ARRAY_FILTER_USE_KEY,
        );

        foreach ($unresolvedTokens as $unresolvedToken) {
            /**
             * The only unresolvable tokens that are valid should be
             * style: form
             * explode: true
             * type: object
             */
            if (!$this->matchesStyle($unresolvedToken, 'form')) {
                continue;
            }

            foreach ($unresolvedParameters as $name => $unresolvedParameter) {
                if ($unresolvedParameter === ['style' => 'form', 'explode' => true]) {
                    $resolvedTokens[$name][] = $unresolvedToken;
                }
            }
        }

        $result = [];

        foreach ($resolvedTokens as $name => $tokens) {
            $result[$name] = $this->parameters[$name]['explode'] ?
                implode('&', $tokens) :
                $tokens[array_key_last($tokens)] ;
        }

        return Result::noResult($result);
    }

    private function matchesStyle(string $token, string $style): bool
    {
        $pattern = match (Style::tryFrom($style)) {
            Style::Form => '#^[^=]+=((?!%20)[^|])*$#',
            Style::SpaceDelimited => '#^[^=]+=[^|,]*$#',
            Style::PipeDelimited => '#^[^=]+=((?!%20)[^,])*$#',
            Style::DeepObject => '#^[^=\[\]]+\[((?!%20)[^,|])+]=((?!%20)[^,|])*$#',
            default => null,
        };

        return is_string($pattern) && preg_match($pattern, $token) === 1;
    }
}
