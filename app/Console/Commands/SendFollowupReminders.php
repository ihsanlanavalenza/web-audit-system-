<?php

namespace App\Console\Commands;

use App\Mail\FollowupDataRequestMail;
use App\Models\DataRequest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendFollowupReminders extends Command
{
    private const LEVEL_FIRST = 1;
    private const LEVEL_SECOND = 2;

    protected $signature = 'audit:send-followup';
    protected $description = 'Kirim email follow-up milestone (7 hari dan 15 hari) ke auditi & auditor';

    public function handle(): int
    {
        $this->info('🔍 Mencari data request yang overdue untuk follow-up milestone...');

        // Cari data request yang:
        // 1. expected_received sudah lewat jatuh tempo
        // 2. Belum ada file yang diupload (input_file = null atau '[]')
        /** @var \Illuminate\Database\Eloquent\Collection<int, DataRequest> $overdueRequests */
        $overdueRequests = DataRequest::query()->where(function (Builder $q) {
            $q->whereNull('input_file')
                ->orWhere('input_file', '[]')
                ->orWhere('input_file', '')
                ->orWhere('input_file', 'null');
        })
            ->whereNotNull('expected_received')
            ->whereDate('expected_received', '<', now()->startOfDay())
            ->where('status', '!=', DataRequest::STATUS_NOT_APPLICABLE)
            ->with(['client', 'kapProfile.user'])
            ->get();

        if ($overdueRequests->isEmpty()) {
            $this->info('✅ Tidak ada data request yang perlu di-followup.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($overdueRequests as $request) {
            /** @var DataRequest $request */
            $daysOverdue = max(1, (int) $request->expected_received->startOfDay()->diffInDays(now()->startOfDay()));
            $followupLevel = $this->resolveFollowupLevel($request, $daysOverdue);

            if ($followupLevel === null) {
                continue;
            }

            $client = $request->client;
            $kap = $request->kapProfile;

            if (!$client || !$kap) {
                $this->warn("⚠️ Data request #{$request->id} missing client/kap, skipping.");
                continue;
            }

            $auditiEmails = Invitation::query()
                ->where('role', 'auditi')
                ->where('client_id', $client->id)
                ->where('kap_id', $kap->id)
                ->whereNotNull('accepted_at')
                ->where(function (Builder $q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->pluck('email');

            $auditorEmails = User::query()
                ->where('role', 'auditor')
                ->whereHas('clients', function (Builder $q) use ($client) {
                    $q->where('clients.id', $client->id);
                })
                ->pluck('email');

            $recipientEmails = $auditiEmails
                ->merge($auditorEmails)
                ->map(fn($email) => strtolower(trim((string) $email)))
                ->filter()
                ->unique()
                ->values();

            if ($recipientEmails->isEmpty()) {
                $this->warn("⚠️ Tidak ada penerima aktif untuk client '{$client->nama_client}', skipping.");
                continue;
            }

            $sentForRequest = 0;

            foreach ($recipientEmails as $recipientEmail) {
                try {
                    Mail::to($recipientEmail)->send(
                        new FollowupDataRequestMail(
                            $request,
                            $client->nama_client,
                            $kap->nama_kap,
                            $daysOverdue,
                            $followupLevel,
                        )
                    );

                    $sentForRequest++;
                    $sent++;

                    $this->info("📧 Follow-up level {$followupLevel} dikirim ke {$recipientEmail} untuk request #{$request->no} ({$request->section})");
                } catch (\Throwable $e) {
                    $this->error("❌ Gagal kirim ke {$recipientEmail}: {$e->getMessage()}");
                }
            }

            if ($sentForRequest > 0) {
                $payload = [
                    'followup_sent_at' => now(),
                ];

                if ($followupLevel === self::LEVEL_FIRST) {
                    $payload['followup_7day_sent_at'] = now();
                }

                if ($followupLevel === self::LEVEL_SECOND) {
                    $payload['followup_15day_sent_at'] = now();
                }

                $request->update($payload);
            }
        }

        $this->info("✅ Selesai! {$sent} email followup terkirim.");
        return self::SUCCESS;
    }

    private function resolveFollowupLevel(DataRequest $request, int $daysOverdue): ?int
    {
        // If job runs late and request already >=15 days overdue,
        // send only the highest pending milestone to avoid duplicate reminders in one day.
        if ($daysOverdue >= 15) {
            return $request->followup_15day_sent_at ? null : self::LEVEL_SECOND;
        }

        if ($daysOverdue >= 7) {
            return $request->followup_7day_sent_at ? null : self::LEVEL_FIRST;
        }

        return null;
    }
}
