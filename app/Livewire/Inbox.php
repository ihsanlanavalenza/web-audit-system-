<?php

namespace App\Livewire;

use App\Models\EmailLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Inbox extends Component
{
    public ?int $selectedEmailId = null;

    public function selectEmail(int $id): void
    {
        $email = EmailLog::query()
            ->forUser(Auth::id())
            ->findOrFail($id);

        $email->markAsRead();
        $this->selectedEmailId = $id;
    }

    public function markAllAsRead(): void
    {
        EmailLog::query()
            ->forUser(Auth::id())
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function closeDetail(): void
    {
        $this->selectedEmailId = null;
    }

    #[Layout('layouts.app', ['title' => 'Inbox'])]
    public function render()
    {
        $userId = Auth::id();

        $emails = EmailLog::query()
            ->forUser($userId)
            ->latest()
            ->get();

        $selectedEmail = $this->selectedEmailId
            ? EmailLog::query()->forUser($userId)->find($this->selectedEmailId)
            : null;

        $unreadCount = EmailLog::query()
            ->forUser($userId)
            ->unread()
            ->count();

        return view('livewire.inbox', [
            'emails' => $emails,
            'selectedEmail' => $selectedEmail,
            'unreadCount' => $unreadCount,
        ]);
    }
}
