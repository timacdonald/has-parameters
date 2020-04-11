<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Closure;
use Illuminate\Http\Request;
use TiMacDonald\Middleware\HasParameters;

class Required
{
    use HasParameters;

    public function handle(Request $request, Closure $next, string $required): void
    {
        //
    }
}
