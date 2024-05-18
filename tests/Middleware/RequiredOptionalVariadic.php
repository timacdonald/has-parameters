<?php

namespace Tests\Middleware;

use Closure;
use Illuminate\Http\Request;
use TiMacDonald\Middleware\HasParameters;

class RequiredOptionalVariadic
{
    use HasParameters;

    public function handle(Request $request, Closure $next, string $required, string $optional = 'default', string ...$variadic): void
    {
        //
    }
}
