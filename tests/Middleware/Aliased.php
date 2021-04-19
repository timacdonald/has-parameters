<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Closure;
use Illuminate\Http\Request;
use TiMacDonald\Middleware\HasParameters;

class Aliased
{
    use HasParameters;

    public function handle(Request $request, Closure $next, string $originalFirst, string $originalSecond): void
    {
        //
    }

    protected static function parameterAliasMap(): array
    {
        return [
            'aliasedFirst' => 'originalFirst',
        ];
    }
}
