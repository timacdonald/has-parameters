<?php

namespace Tests\Middleware;

use Closure;
use Illuminate\Http\Request;
use TiMacDonald\Middleware\HasParameters;

class Optional
{
    use HasParameters;

    public function handle(Request $request, Closure $next, string $optional = 'default'): void
    {
        //
    }
}
