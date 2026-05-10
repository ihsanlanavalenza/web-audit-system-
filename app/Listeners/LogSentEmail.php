<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogSentEmail
{
    /**
     * Automatically log every sent email to the email_logs table.
     * This captures the rendered HTML body, subject, and resolves
     * the user_id from the recipient email address.
     */
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;

            $subject = $message->getSubject() ?? '(Tanpa Subjek)';
            $body = $message->getHtmlBody() ?? $message->getTextBody() ?? '';

            // Get the mailable class name if available
            $mailableClass = null;
            if (isset($event->data['__laravel_notification'])) {
                $mailableClass = $event->data['__laravel_notification'];
            } elseif (isset($event->data['__mailable'])) {
                $mailableClass = $event->data['__mailable'];
            }

            // Extract all recipient email addresses from To field
            $recipients = $message->getTo();

            foreach ($recipients as $recipient) {
                $email = strtolower(trim($recipient->getAddress()));

                // Resolve user_id from the users table
                $userId = User::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->value('id');

                EmailLog::create([
                    'user_id' => $userId,
                    'recipient_email' => $email,
                    'subject' => $subject,
                    'body' => $body,
                    'mailable_class' => $mailableClass,
                ]);
            }
        } catch (\Throwable $e) {
            // Never let email logging failures break the actual email sending flow
            Log::error('Failed to log sent email: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
