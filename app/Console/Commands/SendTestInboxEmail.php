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
        $email = $this->argument('email') ?: 'gunawanrobi171@gmail.com';

        $this->info("📧 Mengirim test email ke: {$email}");

        try {
            Mail::raw('Ini adalah test email untuk fitur Inbox WebAudit (Bypass DB). Jika Anda menerima pesan ini, artinya konfigurasi SMTP (Gmail) Anda sudah BERHASIL! 🎉', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test SMTP WebAudit — ' . now()->format('d M Y H:i:s'));
            });

            $this->info("✅ Test email berhasil dikirim via SMTP!");
        } catch (\Exception $e) {
            $this->error("❌ Gagal mengirim email: " . $e->getMessage());
        }

        return self::SUCCESS;
    }
}
