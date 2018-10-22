<?php

namespace DummyApp\Jobs;

use CloudCreativity\LaravelJsonApi\Queue\ClientDispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateDownload implements ShouldQueue
{

    use Dispatchable,
        ClientDispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var string
     */
    public $category;

    /**
     * CreateDownload constructor.
     *
     * @param string $category
     */
    public function __construct(string $category)
    {
        $this->category = $category;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // no-op
    }
}
