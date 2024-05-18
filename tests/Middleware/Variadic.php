<?php

namespace Tests\Middleware;

use Closure;
use Illuminate\Http\Request;
use TiMacDonald\Middleware\HasParameters;

class Variadic
{
    use HasParameters;

    public function handle(Request $request, Closure $next, string ...$variadic): void
    {
        //
    }
}
