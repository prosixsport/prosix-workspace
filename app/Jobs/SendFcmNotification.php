<?php

namespace App\Jobs;

use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendFcmNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 20;

    public function __construct(
        public ?string $token,
        public string $title,
        public string $body,
        public array $data = []
    ) {
    }

    public function handle(): void
    {
        if (!$this->token) {
            return;
        }

        FcmService::send(
            $this->token,
            $this->title,
            $this->body,
            $this->data
        );
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
