<?php

declare(strict_types=1);

namespace TiMacDonald\Middleware;



use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionMethod;
use ReflectionParameter;
use TypeError;

trait HasParameters
{
    /**
     * @param array $arguments
     */
    public static function with($arguments): string
    {
        $arguments = new Collection($arguments);

        $parameters = self::parameters();

        self::validateArgumentMapIsAnAssociativeArray($arguments);

        $aliases = new Collection(
            /** @phpstan-ignore-next-line */
            static::parameterAliasMap()
        );

        if ($aliases->isNotEmpty()) {
            self::validateAliasesReferenceParameters($parameters, $aliases);

            self::validateAliasesDontPointToSameParameters($aliases);

            self::validateOriginalAndAliasHaveNotBeenPassed($arguments, $aliases);

            $arguments = self::normaliseArguments($arguments, $aliases);
        }

        self::validateNoUnexpectedArguments($parameters, $arguments);

        self::validateParametersAreOptional($parameters->diffKeys($arguments));

        $arguments = self::parseArgumentMap($parameters, new Collection($arguments));

        return self::formatArguments($arguments);
    }

    /**
     * @param array $arguments
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

    private static function formatArguments(Collection $arguments): string
    {
        if ($arguments->isEmpty()) {
            return static::class;
        }

        return static::class.':'.$arguments->implode(',');
    }

    private static function parseArgumentList(Collection $arguments): Collection
    {
        return $arguments->map(
            /**
             * @param mixed $argument
             */
            static function ($argument): string {
                return self::castToString($argument);
            }
        );
    }

    private static function parseArgumentMap(Collection $parameters, Collection $arguments): Collection
    {
        return $parameters->map(static function (ReflectionParameter $parameter) use ($arguments): ?string {
            if ($parameter->isVariadic()) {
                return self::parseVariadicArgument($parameter, $arguments);
            }

            return self::parseStandardArgument($parameter, $arguments);
        })->reject(static function (?string $argument): bool {
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

    private static function parseVariadicArgument(ReflectionParameter $parameter, Collection $arguments): ?string
    {
        if (! $arguments->has($parameter->getName())) {
            return null;
        }

        $values = new Collection($arguments->get($parameter->getName()));

        if ($values->isEmpty()) {
            return null;
        }

        return $values->map(
            /**
             * @param mixed $value
             */
            static function ($value) {
                return self::castToString($value);
            }
        )->implode(',');
    }

    private static function parseStandardArgument(ReflectionParameter $parameter, Collection $arguments): string
    {
        if ($arguments->has($parameter->getName())) {
            return self::castToString($arguments->get($parameter->getName()));
        }

        return self::castToString($parameter->getDefaultValue());
    }

    private static function parameters(): Collection
    {
        $handle = new ReflectionMethod(static::class, 'handle');

        return Collection::make($handle->getParameters())
            ->slice(2)
            ->keyBy(static function (ReflectionParameter $parameter): string {
                return $parameter->getName();
            });
    }

    /**
     * @param mixed $value
     */
    private static function castToString($value): string
    {
        if ($value === false) {
            return '0';
        }

        return (string) $value;
    }

    private static function normaliseArguments(Collection $arguments, Collection $aliases): Collection
    {
        return $arguments->mapWithKeys(
            /** @param mixed $value */
            static function ($value, string $name) use ($aliases): array {
                if ($aliases->has($name)) {
                    /** @var string */
                    $newName = $aliases[$name];

                    return [$newName => $value];
                }

                return [$name => $value];
            }
        );
    }

    private static function validateParametersAreOptional(Collection $parameters): void
    {
        /** @var ?ReflectionParameter */
        $missingRequiredParameter = $parameters->reject(static function (ReflectionParameter $parameter): bool {
            return $parameter->isDefaultValueAvailable() || $parameter->isVariadic();
        })
            ->first();

        if ($missingRequiredParameter === null) {
            return;
        }

        throw new TypeError('Missing required argument $'.$missingRequiredParameter->getName().' for middleware '.static::class.'::handle()');
    }

    private static function validateArgumentListIsNotAnAssociativeArray(Collection $arguments): void
    {
        if (Arr::isAssoc($arguments->all())) {
            throw new TypeError('Expected a non-associative array in HasParameters::in() but received an associative array. You should use the HasParameters::with() method instead.');
        }
    }

    private static function validateArgumentMapIsAnAssociativeArray(Collection $arguments): void
    {
        if ($arguments->isNotEmpty() && ! Arr::isAssoc($arguments->all())) {
            throw new TypeError('Expected an associative array in HasParameters::with() but received a non-associative array. You should use the HasParameters::in() method instead.');
        }
    }

    private static function validateNoUnexpectedArguments(Collection $parameters, Collection $arguments): void
    {
        /** @var ?string */
        $unexpectedArgument = $arguments->keys()
            ->first(static function (string $name) use ($parameters): bool {
                return ! $parameters->has($name);
            });

        if ($unexpectedArgument === null) {
            return;
        }

        throw new TypeError('Unknown argument $'.$unexpectedArgument.' passed to middleware '.static::class.'::handle()');
    }

    private static function validateOriginalAndAliasHaveNotBeenPassed(Collection $arguments, Collection $aliases): void
    {
        if ($arguments->intersectByKeys($aliases->flip())->isNotEmpty()) {
            throw new TypeError('Cannot pass an original parameter and an aliases parameter name at the same time.');
        }
    }

    private static function validateAliasesDontPointToSameParameters(Collection $aliases): void
    {
        if ($aliases->unique()->count() !== $aliases->count()) {
            throw new TypeError('Two provided aliases cannot point to the same parameter.');
        }
    }

    private static function validateAliasesReferenceParameters(Collection $parameters, Collection $aliases): void
    {
        if ($aliases->flip()->diffKeys($parameters)->isNotEmpty()) {
            throw new TypeError('Aliases must reference existing parameters.');
        }
    }
}
