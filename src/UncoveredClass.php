<?php

declare(strict_types=1);

namespace TiMacDonald\Middleware;

class UncoveredClass
{
    public function someNewMethodWithNoCoverage(): void
    {
        if ('test') {
            if ('test') {
                if ('test') {
                    if ('test') {
                        if ('test') {
                            if ('test') {
                                if ('test') {
                                    echo 'hello world';
                                }
                            }
                        }
                    }
                }
            }
        }

        echo 'hello world';
        echo 'hello world';
        echo 'hello world';
        echo 'hello world';
        echo 'hello world';
        echo 'hello world';
        echo 'hello world';
        echo 'hello world';
        echo 'hello world';
        echo 'hello world';
        echo 'hello world';
        echo 'hello world';
    }

    public function anotherOne(): void
    {
        echo 'here we go again';
        echo 'here we go again';
        echo 'here we go again';
        echo 'here we go again';
        echo 'here we go again';
        echo 'here we go again';

        if ('test') {
            if ('test') {
                if ('test') {
                    if ('test') {
                        echo 'hello world';
                    }
                }
            }
        }
    }
}
