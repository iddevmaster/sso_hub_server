<div class="container">
    <h3 class="text-center">Edit Permission</h3>
    <p class="fw-bold">User Information</p>
    <div class="row row-cols-2 mb-4">
        <p class="mb-0">Name: <span class="text-decoration-underline ms-2">{{ $user->name }}</span></p>
        <p class="mb-0">Username: <span class="text-decoration-underline ms-2">{{ $user->email }}</span></p>
        <p class="mb-0">Dpm: <span class="text-decoration-underline ms-2">{{ optional($user->getDpm)->name }}</span></p>
        <p class="mb-0">Dpm: <span class="text-decoration-underline ms-2">{{ optional($user->getBrn)->name }}</span></p>
        <p class="mb-0">Dpm: <span class="text-decoration-underline ms-2">{{ optional($user->getAgn)->name }}</span></p>
        <p class="mb-0">Role: <span class="text-decoration-underline ms-2">{{ $user->role ? $user->role : '-' }}</span></p>
    </div>

    <p class="fw-bold">User Permission</p>
    <form action="" wire:submit="updatePerm">
        <div class="row row-cols-2 px-2 mb-4">
            @foreach ($perms as $index => $perm)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model="selectedPerms.{{ $perm->name }}" value="1" id="perm{{ $index }}">
                    <label class="form-check-label" for="perm{{ $index }}">
                        {{ $perm->name }}
                    </label>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center gap-1">
            <button type="submit" class="btn btn-success">Save</button>
            <button type="button" class="btn btn-danger" wire:click="cancelPerm()">Cancel</button>
        </div>
    </form>
</div>
