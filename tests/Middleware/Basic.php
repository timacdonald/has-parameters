<?php

namespace Tests\Middleware;

use Closure;
use Illuminate\Http\Request;
use TiMacDonald\Middleware\HasParameters;

class Basic
{
    use HasParameters;

    public function handle(Request $request, Closure $next): void
    {
        //
    }
}
