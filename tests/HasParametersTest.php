<?php

declare(strict_types=1);

namespace Tests;

use TypeError;
use ErrorException;
use Tests\Middleware\Basic;
use Tests\Middleware\Optional;
use Tests\Middleware\Required;
use Tests\Middleware\Variadic;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Collection;
use Tests\Middleware\OptionalRequired;
use Tests\Middleware\RequiredOptionalVariadic;

class HasParametersTest extends TestCase
{
    public function testList(): void
    {
        $result = Basic::in([]);
        $this->assertSame('Tests\\Middleware\\Basic', $result);

        $result = Basic::in([null]);
        $this->assertSame('Tests\\Middleware\\Basic:', $result);

        $result = Basic::in(['']);
        $this->assertSame('Tests\\Middleware\\Basic:', $result);

        $result = Basic::in([' ']);
        $this->assertSame('Tests\\Middleware\\Basic: ', $result);

        $result = Basic::in([1.2]);
        $this->assertSame('Tests\\Middleware\\Basic:1.2', $result);

        $result = Basic::in(['laravel']);
        $this->assertSame('Tests\\Middleware\\Basic:laravel', $result);

        $result = Basic::in(['laravel', 'vue']);
        $this->assertSame('Tests\\Middleware\\Basic:laravel,vue', $result);

        $result = Basic::in(['laravel', ' ', null, 'tailwind']);
        $this->assertSame('Tests\\Middleware\\Basic:laravel, ,,tailwind', $result);

        $result = Basic::in(new Collection(['laravel', 'vue']));
        $this->assertSame('Tests\\Middleware\\Basic:laravel,vue', $result);

        $result = Basic::in([new Collection(['laravel', 'vue'])]);
        $this->assertSame('Tests\\Middleware\\Basic:["laravel","vue"]', $result);

        $result = Basic::in([true, false]);
        $this->assertSame('Tests\\Middleware\\Basic:1,0', $result);

        $result = Variadic::in([]);
        $this->assertSame('Tests\\Middleware\\Variadic', $result);

        $result = Variadic::in(['laravel', 'vue']);
        $this->assertSame('Tests\\Middleware\\Variadic:laravel,vue', $result);

        $result = RequiredOptionalVariadic::in(['laravel']);
        $this->assertSame('Tests\\Middleware\\RequiredOptionalVariadic:laravel', $result);

        $result = RequiredOptionalVariadic::in(['laravel', 'vue', 'tailwind', 'react']);
        $this->assertSame('Tests\\Middleware\\RequiredOptionalVariadic:laravel,vue,tailwind,react', $result);
    }

    public function testListDoesNotAcceptSubArray(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Array to string conversion');

        Basic::in(['laravel', ['vue', 'react']]);
    }

    public function testListDetectsRequiredParametersThatHaveNotBeenProvided(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Missing required argument $required for middleware Tests\\Middleware\\OptionalRequired::handle()');

        OptionalRequired::in(['laravel']);
    }

    public function testListDoesNotAcceptAssociativeArray(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Expected a non-associative array in HasParameters::in() but received an associative array. You should use the HasParameters::with() method instead.');

        Basic::in(['framework' => 'laravel']);
    }

    public function testMap(): void
    {
        $result = Required::with(['required' => null]);
        $this->assertSame('Tests\\Middleware\\Required:', $result);

        $result = Required::with(['required' => '']);
        $this->assertSame('Tests\\Middleware\\Required:', $result);

        $result = Required::with(['required' => ' ']);
        $this->assertSame('Tests\\Middleware\\Required: ', $result);

        $result = Required::with(['required' => false]);
        $this->assertSame('Tests\\Middleware\\Required:0', $result);

        $result = Required::with(['required' => true]);
        $this->assertSame('Tests\\Middleware\\Required:1', $result);

        $result = Required::with(['required' => 'laravel']);
        $this->assertSame('Tests\\Middleware\\Required:laravel', $result);

        $result = Required::with(['required' => 1.2]);
        $this->assertSame('Tests\\Middleware\\Required:1.2', $result);

        $result = Required::with(new Collection(['required' => 'laravel']));
        $this->assertSame('Tests\\Middleware\\Required:laravel', $result);

        $result = Required::with(['required' => new Collection(['laravel', 'vue'])]);
        $this->assertSame('Tests\\Middleware\\Required:["laravel","vue"]', $result);

        $result = Optional::with([]);
        $this->assertSame('Tests\\Middleware\\Optional:default', $result);

        $result = Optional::with(['optional' => null]);
        $this->assertSame('Tests\\Middleware\\Optional:', $result);

        $result = Optional::with(['optional' => '']);
        $this->assertSame('Tests\\Middleware\\Optional:', $result);

        $result = Optional::with(['optional' => ' ']);
        $this->assertSame('Tests\\Middleware\\Optional: ', $result);

        $result = Optional::with(['optional' => 1.2]);
        $this->assertSame('Tests\\Middleware\\Optional:1.2', $result);

        $result = Optional::with(['optional' => 'laravel']);
        $this->assertSame('Tests\\Middleware\\Optional:laravel', $result);

        $result = Optional::with(new Collection(['optional' => 'laravel']));
        $this->assertSame('Tests\\Middleware\\Optional:laravel', $result);

        $result = Optional::with(['optional' => new Collection(['laravel', 'vue'])]);
        $this->assertSame('Tests\\Middleware\\Optional:["laravel","vue"]', $result);

        $result = Optional::with(['optional' => true]);
        $this->assertSame('Tests\\Middleware\\Optional:1', $result);

        $result = Optional::with(['optional' => false]);
        $this->assertSame('Tests\\Middleware\\Optional:0', $result);

        $result = Variadic::with(['variadic' => '']);
        $this->assertSame('Tests\\Middleware\\Variadic:', $result);

        $result = Variadic::with(['variadic' => ' ']);
        $this->assertSame('Tests\\Middleware\\Variadic: ', $result);

        $result = Variadic::with(['variadic' => 1.2]);
        $this->assertSame('Tests\\Middleware\\Variadic:1.2', $result);

        $result = Variadic::with(['variadic' => 'laravel']);
        $this->assertSame('Tests\\Middleware\\Variadic:laravel', $result);

        $result = Variadic::with(['variadic' => ['laravel', 'vue']]);
        $this->assertSame('Tests\\Middleware\\Variadic:laravel,vue', $result);

        $result = Variadic::with(['variadic' => ['laravel', ' ', null, 'vue']]);
        $this->assertSame('Tests\\Middleware\\Variadic:laravel, ,,vue', $result);

        $result = Variadic::with(['variadic' => new Collection(['laravel', 'vue'])]);
        $this->assertSame('Tests\\Middleware\\Variadic:laravel,vue', $result);

        $result = Variadic::with(['variadic' => [new Collection(['laravel', 'vue'])]]);
        $this->assertSame('Tests\\Middleware\\Variadic:["laravel","vue"]', $result);

        $result = Variadic::with(['variadic' => true]);
        $this->assertSame('Tests\\Middleware\\Variadic:1', $result);

        $result = Variadic::with(['variadic' => false]);
        $this->assertSame('Tests\\Middleware\\Variadic:0', $result);

        $result = OptionalRequired::with(['required' => 'laravel']);
        $this->assertSame('Tests\\Middleware\\OptionalRequired:default,laravel', $result);

        $result = OptionalRequired::with(['required' => 'laravel', 'optional' => 'vue']);
        $this->assertSame('Tests\\Middleware\\OptionalRequired:vue,laravel', $result);

        $result = RequiredOptionalVariadic::with(['required' => 'laravel']);
        $this->assertSame('Tests\\Middleware\\RequiredOptionalVariadic:laravel,default', $result);

        $result = RequiredOptionalVariadic::with(['required' => 'laravel', 'optional' => 'vue']);
        $this->assertSame('Tests\\Middleware\\RequiredOptionalVariadic:laravel,vue', $result);

        $result = RequiredOptionalVariadic::with(['required' => 'laravel', 'optional' => 'vue', 'variadic' => 'tailwind']);
        $this->assertSame('Tests\\Middleware\\RequiredOptionalVariadic:laravel,vue,tailwind', $result);

        $result = RequiredOptionalVariadic::with(['required' => 'laravel', 'optional' => 'vue', 'variadic' => ['tailwind', 'react']]);
        $this->assertSame('Tests\\Middleware\\RequiredOptionalVariadic:laravel,vue,tailwind,react', $result);

        $result = RequiredOptionalVariadic::with(['required' => 'laravel', 'optional' => 'vue', 'variadic' => []]);
        $this->assertSame('Tests\\Middleware\\RequiredOptionalVariadic:laravel,vue', $result);
    }

    public function testMapDoesNotAcceptSubArray(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Array to string conversion');

        Required::with(['required' => ['vue', 'react']]);
    }

    public function testMapMustContainRequiredArguments(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Missing required argument $required for middleware Tests\\Middleware\\RequiredOptionalVariadic::handle()');

        RequiredOptionalVariadic::with(['optional' => 'vue']);
    }

    public function testMapMustHaveEnoughRequiredArguments(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Missing required argument $required for middleware Tests\\Middleware\\Required::handle()');

        Required::with([]);
    }

    public function testMapDoesNotAcceptANonAssociativeArray(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Expected an associative array in HasParameters::with() but received a non-associative array. You should use the HasParameters::in() method instead.');

        Basic::with(['framework', 'laravel']);
    }

    public function testMapMustPassCorrectRequiredArguments(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Unknown argument $missing passed to middleware Tests\\Middleware\\Required::handle()');

        Required::with(['missing' => 'test']);
    }

    public function testMapVariadicWithIncorrectArgumentName(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Unknown argument $missing passed to middleware Tests\\Middleware\\Variadic::handle()');

        Variadic::with(['missing' => 'laravel']);
    }

    public function testVariadicDoesNotAcceptSubArray(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Array to string conversion');

        Variadic::with(['variadic' => [['laravel', 'vue']]]);
    }

    public function testMiddlewareThatUsesFuncGetArgsCanAccessArgumentsThatAreNotPassedAsParameters(): void
    {
        $result = OptionalRequired::in(['laravel', 'vue', 'tailwind']);
        $this->assertSame('Tests\\Middleware\\OptionalRequired:laravel,vue,tailwind', $result);
    }
}
