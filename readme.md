<p align="center"><img src="/art/header.png" alt="Has Parameters: a Laravel package by Tim MacDonald"></p>

# Has Parameters

A trait for Laravel middleware that allows you to pass arguments in a more PHP'ish way, including as a key => value pair for named parameters,  and as a list for variadic parameters. Improves static analysis / IDE support, allows you to specify arguments by referencing the parameter name, enables skipping optional parameters (which fallback to their default value), and adds some validation so you don't forget any required parameters by accident.

Read more about the why in my blog post [Rethinking Laravel's middleware argument API](https://timacdonald.me/rethinking-laravels-middleware-argument-api/)

## Version support

- **PHP**: 8.1, 8.2, 8.3
- **Laravel**: 10.0, 11.0

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/has-parameters).

```
$ composer require timacdonald/has-parameters
```

## Basic usage

To get started with an example, I'm going to use a stripped back version of Laravel's `ThrottleRequests`. First up, add the `HasParameters` trait to your middleware.

```php
<?php

class ThrottleRequests
{
    use HasParameters;

    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        //
    }
}
```

You can now pass arguments to this middleware using the static `with()` method, using the parameter name as the key.

```php
<?php

Route::stuff()
    ->middleware([
        ThrottleRequests::with([
            'maxAttempts' => 120,
        ]),
    ]);
```

You'll notice at first this is a little more verbose, but I think you'll enjoy the complete feature set after reading these docs and taking it for a spin.

## Middleware::with()

The static `with()` method allows you to easily see which values represent what when declaring your middleware, instead of just declaring a comma seperate list of values.
The order of the keys does not matter. The trait will pair up the keys to the parameter names in the `handle()` method.

```php
<?php

// before...
Route::stuff()
    ->middleware([
        'throttle:10,1' // what does 10 or 1 stand for here?
    ]);

// after...
Route::stuff()
    ->middleware([
        ThrottleRequests::with([
            'decayMinutes' => 1,
            'maxAttempts' => 10,
        ]),
    ]);
```

### Skipping parameters

If any parameters in the `handle` method have a default value, you do not need to pass them through - unless you are changing their value. As an example, if you'd like to only specify a prefix for the `ThrottleRequests` middleware, but keep the `$decayMinutes` and `$maxAttempts` as their default values, you can do the following...

```php
<?php

Route::stuff()
    ->middleware([
        ThrottleRequests::with([
            'prefix' => 'admins',
        ]),
    ]);
```

As we saw previously in the handle method, the default values of `$decayMinutes` is `1` and `$maxAttempts` is `60`. The middleware will receive those values for those parameters, but will now receive `"admins"` for the `$prefix`.

### Arrays for variadic parameters

When your middleware ends in a variadic paramater, you can pass an array of values for the variadic parameter key. Take a look at the following `handle()` method.

```php
<?php

public function handle(Request $request, Closure $next, string $ability, string ...$models)
```

Here is how we can pass a list of values to the variadic `$models` parameter...

```php
<?php

Route::stuff()
    ->middleware([
        Authorize::with([
            'ability' => PostVideoPolicy::UPDATE,
            'models' => [Post::class, Video::class],
        ]),
    ]);
```

### Parameter aliases

Some middleware will have different behaviour based on the type of values passed through to a specific parameter. As an example, Laravel's `ThrottleRequests` middleware allows you to pass the name of a rate limiter to the `$maxAttempts` parameter, instead of a numeric value, in order to utilise that named limiter on the endpoint.

```php
<?php

// a named rate limiter...

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
});

// using the rate limiter WITHOUT an alias...

Route::stuff()
    ->middleware([
        ThrottleRequests::with([
            'maxAttempts' => 'api',
        ]),
    ]);
```

In this kind of scenario, it is nice to be able to alias the `$maxAttempts` parameter name to something more readable.

```php
<?php

Route::stuff()
    ->middleware([
        ThrottleRequests::with([
            'limiter' => 'api',
        ]),
    ]);
```

To achieve this, you can setup a parameter alias map in your middleware...

```php
<?php

class ThrottleRequests
{
    use HasParameters;

    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        //
    }

    protected static function parameterAliasMap(): array
    {
        return [
            'limiter' => 'maxAttempts',
            // 'alias' => 'parameter',
        ];
    }
}
```

### Validation

These validations occur whenever the routes file is loaded or compiled, not just when you hit a route that contains the declaration.

#### Unexpected parameter

Ensures that you do not declare any keys that do not exist as parameter variables in the `handle()` method. This helps make sure you don't mis-type a parameter name.

#### Required parameters

Ensures all required parameters (those without default values) have been provided.

#### Aliases

- Ensures all aliases specified reference an existing parameter.
- Provided aliases don't reference the same parameter.
- An original parameter key and an alias have not both been provided.

## Middleware::in()

The static `in()` method very much reflects and works the same as the existing concatination API. It accepts a list of values, i.e. a non-associative array. You should use this method if your `handle()` method is a single variadic parameter, i.e. expecting a single list of values, as shown in the following middleware handle method...
.
```php
<?php

public function handle(Request $request, Closure $next, string ...$states)
{
    //
}
```

You can pass through a list of "states" to the middleware like so...

```php
<?php

Route::stuff()
    ->middleware([
        EnsurePostState::in([PostState::DRAFT, PostState::UNDER_REVIEW]),
    ]);
```

### Validation

#### Required parameters

Just like the `with()` method, the `in()` method will validate that you have passed enough values through to cover all the required parameters. Because variadic parameters do not require any values to be passed through, you only really rub up against this when you should probably be using the `with()` method.

## Value transformation

You should keep in mind that everything will still be cast to a string. Although you are passing in, for example, integers, the middleware itself will *always* receive a string. This is how Laravel works under-the-hood to implement route caching.

One thing to note is the `false` is actually cast to the string `"0"` to keep some consistency with casting `true` to a string, which results in the string `"1"`.

## Typing values

It is possible to provide stronger types parameters by leaning on docblocks. Here is an example of a strongly typed middleware:

```php
/**
 * @method static string with(array{
 *     maxAttempts?: int,
 *     decayMinutes?: float|int,
 *     prefix?: string,
 * }|'admin' $arguments)
 */
class ThrottleMiddleware
{
    use HasParameters;
}
```

You will then receive autocomplete and diagnostics from your language server:

```php
ThrottleMiddleware::with('admin');
// ✅

ThrottleMiddleware::with(['decayMinutes' => 10]);
// ✅

ThrottleMiddleware::with('foo');
// ❌ fails because 'foo' is not in the allowed string values

ThrottleMiddleware::with(['maxAttempts' => 'ten']);
// ❌ fails because `maxAttempts` must be an int
```

Checkout the example in the [PHPStan playground](https://phpstan.org/r/8c0ba5d8-a730-4fd9-9af8-bcec33d3b043).

## Credits

- [Tim MacDonald](https://github.com/timacdonald)
- [All Contributors](../../contributors)

And a special (vegi) thanks to [Caneco](https://twitter.com/caneco) for the logo ✨

## Thanksware

You are free to use this package, but I ask that you reach out to someone (not me) who has previously, or is currently, maintaining or contributing to an open source library you are using in your project and thank them for their work. Consider your entire tech stack: packages, frameworks, languages, databases, operating systems, frontend, backend, etc.
