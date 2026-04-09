<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount()
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Super Admin → redirect ke admin dashboard
        if ($user && $user->isSuperAdmin()) {
            return $this->redirect(route('admin.dashboard'), navigate: false);
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $data = [];

        if ($user->isAuditor()) {
            $kap = $user->kapProfile;
            $data = [
                'hasKap' => !!$kap,
                'totalClients' => $kap ? $kap->clients()->count() : 0,
                'totalRequests' => $kap ? $kap->dataRequests()->count() : 0,
                'pendingInvites' => $kap ? $kap->invitations()->pending()->count() : 0,
                'statusCounts' => $kap ? $kap->dataRequests()
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                    ->toArray() : [],
            ];
        } else {
            // Auditi - find clients they are invited to
            $invitation = \App\Models\Invitation::where('email', $user->email)
                ->whereNotNull('accepted_at')
                ->first();
            $data = [
                'invitation' => $invitation,
                'clientId' => $invitation?->client_id,
            ];
        }

        return view('livewire.dashboard', $data);
    }
}
