<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Closure;
use Illuminate\Http\Request;
use const PHP_MAJOR_VERSION;
use TiMacDonald\Middleware\HasParameters;

if (PHP_MAJOR_VERSION < 8) {
    class OptionalRequired
    {
        use HasParameters;

        public function handle(Request $request, Closure $next, string $optional, string $required): void
        {
            //
        }
    }
}
