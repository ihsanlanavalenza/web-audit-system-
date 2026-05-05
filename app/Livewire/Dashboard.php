<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\DataRequest;
use App\Models\Invitation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && request()->has('token')) {
            $invitation = Invitation::where('token', request('token'))->first();
            if ($invitation && $invitation->isPending()) {
                Invitation::acceptForUser($user, $invitation);
                session()->flash('success', 'Undangan berhasil diterima.');
                return $this->redirect(route('dashboard'), navigate: false);
            }
        }

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
            $kapId = $user->resolveKapId();
            $accessibleClientIds = $user->clients()->pluck('clients.id');

            $requestsQuery = DataRequest::query()
                ->whereIn('client_id', $accessibleClientIds);

            $data = [
                'hasKap' => !is_null($kapId),
                'totalClients' => $accessibleClientIds->count(),
                'totalRequests' => (clone $requestsQuery)->count(),
                'pendingInvites' => $kapId ? Invitation::query()->where('kap_id', $kapId)->pending()->count() : 0,
                'statusCounts' => (clone $requestsQuery)
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                    ->toArray(),
            ];
        } else {
            // Auditi - find clients they are invited to
            $invitation = Invitation::query()
                ->where('role', 'auditi')
                ->where('email', $user->email)
                ->whereNotNull('accepted_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest('accepted_at')
                ->first();
            $data = [
                'invitation' => $invitation,
                'clientId' => $invitation?->client_id,
            ];
        }

        return view('livewire.dashboard', $data);
    }
}
