<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Queue;

use CloudCreativity\LaravelJsonApi\Queue\ClientDispatchable;
use DummyApp\Download;
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
     * Execute the job.
     *
     * @return Download
     * @throws \Exception
     */
    public function handle(): Download
    {
        if ($this->ex) {
            throw new \LogicException('Boom.');
        }

        $download = factory(Download::class)->create();
        $this->didCreate($download);

        return $download;
    }
}
