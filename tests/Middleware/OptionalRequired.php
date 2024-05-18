<?php

namespace Tests\Middleware;

use Closure;
use Illuminate\Http\Request;
use TiMacDonald\Middleware\HasParameters;

// Cannot have optional parameter before required parameter in PHP >=8.0.
if (PHP_MAJOR_VERSION < 8) {
    class OptionalRequired
    {
        use HasParameters;

        public function handle(Request $request, Closure $next, string $optional = 'default', string $required): void
        {
            //
        }
    }
}
