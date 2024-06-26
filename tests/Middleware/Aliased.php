<?php

namespace Tests\Middleware;

use Closure;
use Illuminate\Http\Request;
use TiMacDonald\Middleware\HasParameters;

class Aliased
{
    use HasParameters;

    public function handle(Request $request, Closure $next, string $originalFirst, string $originalSecond, string $originalThird): void
    {
        //
    }

    /**
     * @return array<string, string>
     */
    private static function parameterAliasMap(): array
    {
        return [
            'aliasedFirst' => 'originalFirst',
            'aliasedThird' => 'originalThird',
        ];
    }
}
