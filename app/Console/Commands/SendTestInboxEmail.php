<?php

namespace App\Console\Commands;

use App\Mail\FollowupDataRequestMail;
use App\Models\DataRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestInboxEmail extends Command
{
    protected $signature = 'inbox:test {email? : Email penerima (default: user pertama di DB)}';
    protected $description = 'Kirim test email untuk menguji fitur Inbox di local';

    public function handle(): int
    {
        $email = $this->argument('email');

        if ($email) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if (!$user) {
                $this->error("User dengan email '{$email}' tidak ditemukan di database.");
                return self::FAILURE;
            }
        } else {
            $user = User::first();
            if (!$user) {
                $this->error('Tidak ada user di database. Jalankan seeder terlebih dahulu.');
                return self::FAILURE;
            }
        }

        $this->info("📧 Mengirim test email ke: {$user->email} ({$user->name})");

        // Cari data request sample, atau buat dummy data
        $dataRequest = DataRequest::first();

        if ($dataRequest) {
            $clientName = $dataRequest->client?->nama_client ?? 'Test Client';
            $kapName = $dataRequest->kapProfile?->nama_kap ?? 'Test KAP';

            Mail::to($user->email)->send(
                new FollowupDataRequestMail(
                    dataRequest: $dataRequest,
                    clientName: $clientName,
                    kapName: $kapName,
                    daysOverdue: 1,
                    followupLevel: 1,
                )
            );

            $this->info("✅ Test email (FollowupDataRequestMail) berhasil dikirim!");
            $this->info("   Subject: Follow-up 1 Hari - Data Audit Belum Diterima — {$dataRequest->section}");
        } else {
            // Fallback: kirim email sederhana jika tidak ada data request
            Mail::raw('Ini adalah test email untuk fitur Inbox WebAudit. Jika Anda melihat pesan ini di halaman Inbox, berarti fitur sudah berfungsi dengan baik! 🎉', function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Test Inbox WebAudit — ' . now()->format('d M Y H:i'));
            });

            $this->info("✅ Test email (plain text) berhasil dikirim!");
        }

        $this->newLine();
        $this->info("👉 Buka halaman Inbox di browser untuk melihat hasilnya.");
        $this->info("   URL: " . url('/inbox'));

        return self::SUCCESS;
    }
}
