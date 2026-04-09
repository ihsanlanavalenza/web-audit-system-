<?php

namespace App\Livewire;

use App\Models\Invitation;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'auditor';
    public string $search = '';
    public bool $showRoleConflictModal = false;
    public ?int $pendingRoleUserId = null;
    public string $pendingRoleValue = '';
    public string $pendingRoleUserName = '';
    public int $pendingConflictCount = 0;

    public function openModal()
    {
        $this->reset(['name', 'email', 'password', 'role', 'editId']);
        $this->role = 'auditor';
        $this->showModal = true;
    }

    public function editUser(int $id)
    {
        $user = User::findOrFail($id);
        $this->editId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = '';
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users,email' . ($this->editId ? ",{$this->editId}" : ''),
            'role' => 'required|in:super_admin,auditor,auditi',
        ];

        if (!$this->editId || $this->password) {
            $rules['password'] = 'required|min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editId) {
            User::findOrFail($this->editId)->update($data);
            session()->flash('success', 'User berhasil diperbarui!');
        } else {
            User::create($data);
            session()->flash('success', 'User berhasil ditambahkan!');
        }

        $this->showModal = false;
    }

    public function deleteUser(int $id)
    {
        /** @var User $user */
        $user = User::findOrFail($id);
        if ($id === (int) Auth::id()) {
            session()->flash('error', 'Tidak bisa menghapus akun sendiri!');
            return;
        }
        $user->delete();
        session()->flash('success', 'User berhasil dihapus!');
    }

    public function changeRole(int $id, string $role)
    {
        if (!in_array($role, ['super_admin', 'auditor', 'auditi'], true)) {
            session()->flash('error', 'Role tidak valid.');
            return;
        }

        /** @var User $user */
        $user = User::findOrFail($id);

        $conflicts = Invitation::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
            ->pending()
            ->active()
            ->where('role', '!=', $role)
            ->get(['id']);

        if ($conflicts->isNotEmpty()) {
            $this->pendingRoleUserId = $user->id;
            $this->pendingRoleValue = $role;
            $this->pendingRoleUserName = $user->name;
            $this->pendingConflictCount = $conflicts->count();
            $this->showRoleConflictModal = true;
            return;
        }

        $this->applyRoleChange($user, $role, []);
    }

    public function confirmRoleChange(bool $cancelConflicts = false)
    {
        if (!$this->pendingRoleUserId || $this->pendingRoleValue === '') {
            $this->resetRoleConflictState();
            return;
        }

        /** @var User $user */
        $user = User::findOrFail($this->pendingRoleUserId);

        $conflicts = Invitation::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
            ->pending()
            ->active()
            ->where('role', '!=', $this->pendingRoleValue)
            ->get(['id']);

        $cancelledIds = [];
        if ($cancelConflicts && $conflicts->isNotEmpty()) {
            $cancelledIds = $conflicts->pluck('id')->all();
            Invitation::whereIn('id', $cancelledIds)->delete();
        }

        $this->applyRoleChange($user, $this->pendingRoleValue, $cancelledIds);
        $this->resetRoleConflictState();
    }

    public function cancelRoleChange(): void
    {
        $this->resetRoleConflictState();
        session()->flash('error', 'Perubahan role dibatalkan.');
    }

    private function applyRoleChange(User $user, string $role, array $cancelledInvitationIds): void
    {
        $oldRole = $user->role;

        $user->update(['role' => $role]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'model_type' => User::class,
            'model_id' => $user->id,
            'action' => 'role_changed_by_admin',
            'description' => 'Role user diubah manual oleh super admin.',
            'old_payload' => [
                'role' => $oldRole,
            ],
            'new_payload' => [
                'role' => $role,
                'cancelled_invitation_ids' => $cancelledInvitationIds,
            ],
        ]);

        if (!empty($cancelledInvitationIds)) {
            session()->flash('success', "Role {$user->name} diubah ke {$role} dan " . count($cancelledInvitationIds) . ' undangan pending dibatalkan.');
            return;
        }

        session()->flash('success', "Role {$user->name} diubah ke {$role}!");
    }

    private function resetRoleConflictState(): void
    {
        $this->showRoleConflictModal = false;
        $this->pendingRoleUserId = null;
        $this->pendingRoleValue = '';
        $this->pendingRoleUserName = '';
        $this->pendingConflictCount = 0;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $users = User::when($this->search, function ($q) {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%");
        })->latest()->paginate(15);

        return view('livewire.user-manager', [
            'users' => $users,
            'title' => 'Kelola User',
        ]);
    }
}
