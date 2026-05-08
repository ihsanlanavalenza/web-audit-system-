<?php

namespace App\Notifications;

use App\Models\DataRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DataRequestOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 120;
    public int $maxExceptions = 3;

    public function __construct(
        public DataRequest $dataRequest,
        public int $daysOverdue,
        public int $followupLevel,
        public string $clientName,
        public string $kapName,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function viaQueues(): array
    {
        return [
            'database' => 'default',
        ];
    }

    public function backoff(): array
    {
        return [60, 180, 600];
    }

    public function toArray(object $notifiable): array
    {
        $followupLabel = $this->followupLevel === 2
            ? 'Follow-up Kedua (15 Hari)'
            : 'Follow-up Pertama (7 Hari)';

        return [
            'data_request_id' => $this->dataRequest->id,
            'message' => "Reminder {$followupLabel}: Data Request {$this->dataRequest->section} terlambat {$this->daysOverdue} hari.",
            'client_name' => $this->clientName,
            'kap_name' => $this->kapName,
            'expected_received' => $this->dataRequest->expected_received?->format('Y-m-d'),
            'followup_level' => $this->followupLevel,
            'days_overdue' => $this->daysOverdue,
        ];
    }
}
