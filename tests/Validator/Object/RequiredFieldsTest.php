<?php
declare(strict_types=1);

namespace Validator\Object;

use Membrane\Result\Result;
use Membrane\Validator\Object\RequiredFields;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Object\RequiredFields
 * @uses \Membrane\Result\Result
 * @uses \Membrane\Result\MessageSet
 * @uses \Membrane\Result\Message
 */
class RequiredFieldsTest extends TestCase
{
    /**
     * @return array
     */
    public function dataSetsForValidResults(): array
    {
        return [
            'no required fields' => [
                [],
                [],
                Result::VALID,
            ],
            'one required field is filled' => [
                ['required-1'],
                [
                    'required-1' => 'value-1',
                ],
                Result::VALID,
            ],
            'multiple required fields are filled' => [
                ['required-1', 'required-2', 'required-3'],
                [
                    'required-1' => 'value-1',
                    'required-2' => 'value-2',
                    'required-3' => 'value-3',
                ],
                Result::VALID,
            ],
            'non-required field has been filled' => [
                ['required-1', 'required-2', 'required-3'],
                [
                    'required-1' => 'value-1',
                    'required-2' => 'value-2',
                    'required-3' => 'value-3',
                    'optional-4' => 'value-4',
                ],
                Result::VALID,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForValidResults
     */
    public function IfAllRequiredFieldsAreFilledReturnValid(array $requiredFields, array $input, int $expected) : void
    {
        $requiredFields = new RequiredFields(...$requiredFields);

        $result = $requiredFields->validate($input);

        self::assertEquals($expected, $result->result);
    }

    /**
     * @return array
     */
    public function dataSetsForInvalidResults(): array
    {
        return [
            'one required field, none filled' => [
                ['required-1'],
                [],
                Result::INVALID,
                'required-1',
            ],
            'one required field, none filled, one optional field filled' => [
                ['required-1'],
                ['optional-2' => 'value-2'],
                Result::INVALID,
                'required-1',
            ],
            'two required fields, none filled' => [
                ['required-1', 'required-2'],
                [],
                Result::INVALID,
                'required-1',
                'required-2',
            ],
            'two required fields, one filled' => [
                ['required-1', 'required-2'],
                [
                    'required-1' => 'value-1'
                ],
                Result::INVALID,
                'required-2',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForInvalidResults
     */
    public function FieldsMustBeFilledToReturnValid(array $requiredFields, array $input, int $expected, string ...$expectedMessages): void
    {
        $expectedNoOfMessages = count($expectedMessages);
        $requiredFields = new RequiredFields(...$requiredFields);

        $result = $requiredFields->validate($input);

        self::assertCount(1, $result->messageSets);
        self::assertCount($expectedNoOfMessages, $result->messageSets[0]->messages);
        for($i = 0; $i < $expectedNoOfMessages; $i++){
            self::assertEquals('%s is a required field', $result->messageSets[0]->messages[$i]->message);
            self::assertEquals($expectedMessages[$i], $result->messageSets[0]->messages[$i]->vars[0]);
        }
        self::assertEquals($expected, $result->result);
    }
}