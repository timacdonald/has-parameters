<?php

namespace TiMacDonald\Middleware;

use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionMethod;
use ReflectionParameter;
use TypeError;

trait HasParameters
{
    /**
     * @param  Collection<string, mixed>|array<string, mixed>  $arguments
     */
    public static function with($arguments): string
    {
        $arguments = new Collection($arguments);

        $parameters = self::parameters();

        self::validateArgumentMapIsAnAssociativeArray($arguments);

        $aliases = new Collection(self::parameterAliasMap());

        if ($aliases->isNotEmpty()) {
            self::validateAliasesReferenceParameters($parameters, $aliases);

            self::validateAliasesDontPointToSameParameters($aliases);

            self::validateOriginalAndAliasHaveNotBeenPassed($arguments, $aliases);

            $arguments = self::normaliseArguments($arguments, $aliases);
        }

        self::validateNoUnexpectedArguments($parameters, $arguments);

        self::validateParametersAreOptional(
            /** @phpstan-ignore argument.type */
            $parameters->diffKeys($arguments)
        );

        $arguments = self::parseArgumentMap($parameters, $arguments);

        return self::formatArguments($arguments);
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $arguments
     */
    public static function in($arguments): string
    {
        $arguments = new Collection($arguments);

        $parameters = self::parameters();

        self::validateArgumentListIsNotAnAssociativeArray($arguments);

        self::validateParametersAreOptional($parameters->slice($arguments->count()));

        $arguments = self::parseArgumentList($arguments);

        return self::formatArguments($arguments);
    }

    /**
     * @infection-ignore-all
     *
     * @return array<string, string>
     */
    protected static function parameterAliasMap(): array
    {
        return [
            // 'alias' => 'parameter',
        ];
    }

    /**
     * @param  Collection<array-key, string>  $arguments
     */
    private static function formatArguments(Collection $arguments): string
    {
        if ($arguments->isEmpty()) {
            return static::class;
        }

        return static::class.':'.$arguments->implode(',');
    }

    /**
     * @param  Collection<int, mixed>  $arguments
     * @return Collection<int, string>
     */
    private static function parseArgumentList(Collection $arguments): Collection
    {
        return $arguments->map(function ($argument): string {
            return self::castToString($argument);
        });
    }

    /**
     * @param  Collection<string, ReflectionParameter>  $parameters
     * @param  Collection<string, mixed>  $arguments
     * @return Collection<string, string>
     */
    private static function parseArgumentMap(Collection $parameters, Collection $arguments): Collection
    {
        /** @phpstan-ignore return.type */
        return $parameters->map(function (ReflectionParameter $parameter) use ($arguments): ?string {
            if ($parameter->isVariadic()) {
                return self::parseVariadicArgument($parameter, $arguments);
            }

            return self::parseStandardArgument($parameter, $arguments);
        })->reject(function (?string $argument): bool {
            /**
             * A null value indicates that the last item in the parameter list
             * is a variadic function that is not expecting any values. Because
             * of the way variadic parameters work, we don't want to pass null,
             * we really want to pass void, so we just filter it out of the
             * list completely. null !== void.
             */
            return $argument === null;
        });
    }

    /**
     * @param  Collection<string, mixed>  $arguments
     */
    private static function parseVariadicArgument(ReflectionParameter $parameter, Collection $arguments): ?string
    {
        if (! $arguments->has($parameter->getName())) {
            return null;
        }

        /** @phpstan-ignore argument.type */
        $values = new Collection($arguments->get($parameter->getName()));

        if ($values->isEmpty()) {
            return null;
        }

        return $values->map(
            /**
             * @param  mixed  $value
             */
            function ($value) {
                return self::castToString($value);
            }
        )->implode(',');
    }

    /**
     * @param  Collection<string, mixed>  $arguments
     */
    private static function parseStandardArgument(ReflectionParameter $parameter, Collection $arguments): string
    {
        if ($arguments->has($parameter->getName())) {
            return self::castToString($arguments->get($parameter->getName()));
        }

        return self::castToString($parameter->getDefaultValue());
    }

    /**
     * @return Collection<string, ReflectionParameter>
     */
    private static function parameters(): Collection
    {
        $handle = new ReflectionMethod(static::class, 'handle');

        return Collection::make($handle->getParameters())
            ->skip(2)
            ->keyBy(function (ReflectionParameter $parameter): string {
                return $parameter->getName();
            });
    }

    /**
     * @param  mixed  $value
     */
    private static function castToString($value): string
    {
        if ($value === false) {
            return '0';
        }

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        /** @phpstan-ignore cast.string */
        return (string) $value;
    }

    /**
     * @param  Collection<string, mixed>  $arguments
     * @param  Collection<string, string>  $aliases
     * @return Collection<string, mixed>
     */
    private static function normaliseArguments(Collection $arguments, Collection $aliases): Collection
    {
        return $arguments->mapWithKeys(
            /** @param mixed $value */
            function ($value, string $name) use ($aliases): array {
                if ($aliases->has($name)) {
                    /** @var string */
                    $newName = $aliases[$name];

                    return [$newName => $value];
                }

                return [$name => $value];
            }
        );
    }

    /**
     * @param  Collection<string, ReflectionParameter>  $parameters
     */
    private static function validateParametersAreOptional(Collection $parameters): void
    {
        /** @var ?ReflectionParameter */
        $missingRequiredParameter = $parameters->reject(function (ReflectionParameter $parameter): bool {
            return $parameter->isDefaultValueAvailable() || $parameter->isVariadic();
        })->first();

        if ($missingRequiredParameter === null) {
            return;
        }

        throw new TypeError('Missing required argument $'.$missingRequiredParameter->getName().' for middleware '.static::class.'::handle()');
    }

    /**
     * @param  Collection<int, mixed>  $arguments
     */
    private static function validateArgumentListIsNotAnAssociativeArray(Collection $arguments): void
    {
        if (Arr::isAssoc($arguments->all())) {
            throw new TypeError('Expected a non-associative array in HasParameters::in() but received an associative array. You should use the HasParameters::with() method instead.');
        }
    }

    /**
     * @param  Collection<string, mixed>  $arguments
     */
    private static function validateArgumentMapIsAnAssociativeArray(Collection $arguments): void
    {
        if ($arguments->isNotEmpty() && ! Arr::isAssoc($arguments->all())) {
            throw new TypeError('Expected an associative array in HasParameters::with() but received a non-associative array. You should use the HasParameters::in() method instead.');
        }
    }

    /**
     * @param  Collection<string, ReflectionParameter>  $parameters
     * @param  Collection<string, mixed>  $arguments
     */
    private static function validateNoUnexpectedArguments(Collection $parameters, Collection $arguments): void
    {
        /** @var ?string */
        $unexpectedArgument = $arguments->keys()
            ->first(function (string $name) use ($parameters): bool {
                return ! $parameters->has($name);
            });

        if ($unexpectedArgument === null) {
            return;
        }

        throw new TypeError('Unknown argument $'.$unexpectedArgument.' passed to middleware '.static::class.'::handle()');
    }

    /**
     * @param  Collection<string, mixed>  $arguments
     * @param  Collection<string, string>  $aliases
     */
    private static function validateOriginalAndAliasHaveNotBeenPassed(Collection $arguments, Collection $aliases): void
    {
        if ($arguments->intersectByKeys($aliases->flip())->isNotEmpty()) {
            throw new TypeError('Cannot pass an original parameter and an aliases parameter name at the same time.');
        }
    }

    /**
     * @param  Collection<string, string>  $aliases
     */
    private static function validateAliasesDontPointToSameParameters(Collection $aliases): void
    {
        if ($aliases->unique()->count() !== $aliases->count()) {
            throw new TypeError('Two provided aliases cannot point to the same parameter.');
        }
    }

    /**
     * @param  Collection<string, ReflectionParameter>  $parameters
     * @param  Collection<string, string>  $aliases
     */
    private static function validateAliasesReferenceParameters(Collection $parameters, Collection $aliases): void
    {
        /** @phpstan-ignore argument.type */
        if ($aliases->flip()->diffKeys($parameters)->isNotEmpty()) {
            throw new TypeError('Aliases must reference existing parameters.');
        }
    }
}
