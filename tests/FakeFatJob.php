<?php

namespace Laravel\Vapor\Tests;

class FakeFatJob
{
    public static $handled = false;
    /**
     * @var string
     */
    private $fat;

    public function __construct(string $fat)
    {
        $this->fat = $fat;
    }


    public function handle()
    {
        static::$handled = true;
    }
}
