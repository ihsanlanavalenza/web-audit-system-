<?php

namespace App\Console\Commands;

use App\Mail\FollowupDataRequestMail;
use App\Models\DataRequest;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendFollowupReminders extends Command
{
    protected $signature = 'audit:send-followup';
    protected $description = 'Kirim email reminder ke auditi & auditor untuk data request yang lewat jatuh tempo';

    public function handle(): int
    {
        $this->info('🔍 Mencari data request yang terlambat...');

        // Cari data request yang:
        // 1. expected_received sudah lewat jatuh tempo
        // 2. Belum ada file yang diupload (input_file = null atau '[]')
        // 3. Belum pernah dikirim followup (followup_sent_at = null)
        /** @var \Illuminate\Database\Eloquent\Collection<int, DataRequest> $overdueRequests */
        $overdueRequests = DataRequest::query()->where(function (Builder $q) {
            $q->whereNull('input_file')
                ->orWhere('input_file', '[]')
                ->orWhere('input_file', '')
                ->orWhere('input_file', 'null');
        })
            ->whereNull('followup_sent_at')
            ->whereNotNull('expected_received')
            ->where('expected_received', '<', now()->startOfDay())
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
            $client = $request->client;
            $kap = $request->kapProfile;

            if (!$client || !$kap) {
                $this->warn("⚠️ Data request #{$request->id} missing client/kap, skipping.");
                continue;
            }

            // Cari email auditi dari invitation
            $invitation = Invitation::where('client_id', $client->id)
                ->where('kap_id', $kap->id)
                ->whereNotNull('accepted_at')
                ->first();

            if (!$invitation || !$invitation->email) {
                $this->warn("⚠️ Tidak ada auditi terdaftar untuk client '{$client->nama_client}', skipping.");
                continue;
            }

            $daysOverdue = (int) now()->diffInDays($request->expected_received);

            // Dapatkan email auditor
            $auditorEmail = $kap->user ? $kap->user->email : null;

            try {
                $mail = Mail::to($invitation->email);

                if ($auditorEmail) {
                    $mail->cc($auditorEmail);
                }

                $mail->send(
                    new FollowupDataRequestMail(
                        dataRequest: $request,
                        clientName: $client->nama_client,
                        kapName: $kap->nama_kap,
                        daysOverdue: $daysOverdue,
                    )
                );

                // Tandai sudah dikirim
                $request->update(['followup_sent_at' => now()]);
                $sent++;

                $this->info("📧 Email dikirim ke {$invitation->email} untuk request #{$request->no} ({$request->section})");
            } catch (\Exception $e) {
                $this->error("❌ Gagal kirim ke {$invitation->email}: {$e->getMessage()}");
            }
        }

        $this->info("✅ Selesai! {$sent} email followup terkirim.");
        return self::SUCCESS;
    }
}
