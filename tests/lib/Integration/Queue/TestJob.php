<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Queue;

use CloudCreativity\LaravelJsonApi\Queue\ClientDispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestJob implements ShouldQueue
{

    use Dispatchable,
        ClientDispatchable,
        SerializesModels,
        InteractsWithQueue,
        Queueable;

    /**
     * @var bool
     */
    public $ex = false;

    /**
     * @var int
     */
    public $tries = 2;

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        if ($this->ex) {
            throw new \LogicException('Boom.');
        }
    }
}
