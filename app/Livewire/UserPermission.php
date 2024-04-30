<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class UserPermission extends Component
{
    public $users;
    public $perms;
    public $user;
    public $selectedPerms = [];
    public $editPerm = false;

    public function mount()
    {
        $this->users = User::all();
        $this->perms = Permission::all();
    }

    public function getUserPerm($userid) {
        $this->user = User::find($userid);
        foreach ($this->perms as $perm) {
            if ($this->user->can($perm->name)) {
                $this->selectedPerms[$perm->name] = true;
            } else {
                $this->selectedPerms[$perm->name] = false;
            }
        }
        $this->editPerm = true;
    }

    public function updatePerm() {
        foreach ($this->selectedPerms as $key => $permCon) {
            if ($permCon) {
                $this->user->givePermissionTo($key);
            } else {
                $this->user->revokePermissionTo($key);
            }
        }
        $this->editPerm = false;
        // refresh page
        return redirect()->route('user.perm');
    }

    public function cancelPerm() {
        $this->editPerm = false;
    }

    public function render()
    {
        if ($this->editPerm) {
            return view('livewire.edit-perm');
        } else {
            return view('livewire.user-permission');
        }
    }
}
