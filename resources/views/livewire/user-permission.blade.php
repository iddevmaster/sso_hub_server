<div>
    <table class="table table-hover display" id="upermTable">
        <thead>
            <tr class="table-dark">
                <th>#</th>
                <th>Name</th>
                <th>Username</th>
                <th>Department</th>
                <th>Branch</th>
                <th>Agency</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $index => $user)
                <tr>
                    <th>{{ $index + 1 }}</th>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ optional($user->getDpm)->name }}</td>
                    <td>{{ optional($user->getBrn)->name }}</td>
                    <td>{{ optional($user->getAgn)->name }}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" wire:click="getUserPerm({{ $user->id }})"><i class="bi bi-gear"></i></button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
