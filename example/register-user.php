<?php

declare(strict_types=1);

include_once __DIR__ . '/../vendor/autoload.php';

use Membrane\Attribute\Builder;
use Membrane\Attribute\FilterOrValidator;
use Membrane\Attribute\OverrideProcessorType;
use Membrane\Attribute\Placement;
use Membrane\Attribute\SetFilterOrValidator;
use Membrane\Attribute\Subtype;
use Membrane\Filter\CreateObject\WithNamedArguments;
use Membrane\Filter\Shape\Collect;
use Membrane\Filter\Shape\Delete;
use Membrane\Processor\ProcessorType;
use Membrane\Result\FieldName;
use Membrane\Validator\Collection\Identical;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\String\Length;

class Password
{
    public readonly string $hashedPassword;

    public function __construct(string $password)
    {
        $this->hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    }
}

#[SetFilterOrValidator(new RequiredFields('username', 'password', 'confirm_password'), Placement::BEFORE)]
#[SetFilterOrValidator(new Collect('password', 'password', 'confirm_password'), Placement::BEFORE)]
#[SetFilterOrValidator(new Delete('confirm_password'), Placement::BEFORE)]
#[SetFilterOrValidator(new WithNamedArguments(RegisterUser::class), Placement::AFTER)]
class RegisterUser
{
    public function __construct(
        #[FilterOrValidator(new Length(5, 20))]
        public readonly string $username,

        #[SetFilterOrValidator(new Identical(), Placement::BEFORE)]
        #[FilterOrValidator(new Length(10))]
        #[SetFilterOrValidator(new WithNamedArguments(Password::class), Placement::AFTER)]
        #[OverrideProcessorType(ProcessorType::Collection)]
        #[Subtype('string')]
        public readonly Password $password
    ) {
    }
}

$builder = new Builder();
$processor = $builder->fromClass(RegisterUser::class);

$payload = [
    'username' => 'Freddy',
    'password' => 'password123',
    'confirm_password' => 'password123',
];

$result = $processor->process(new FieldName(''), $payload);

var_dump($result);

$invalidPayload = [
    'username' => 'Fred',
    'password' => 'password123',
    'confirm_password' => 'password',
];

$result = $processor->process(new FieldName(''), $invalidPayload);

var_dump($result);
