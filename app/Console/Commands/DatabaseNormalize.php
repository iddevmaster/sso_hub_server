<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Console\Command;

class DatabaseNormalize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:database-normalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        try {
            echo "Updating User brn and agn ...\n";
            $users = User::all(['id', 'brn', 'agn']);
            foreach ($users as $user) {
                $branch = Branch::where('name', $user->brn)->orWhere('id', $user->brn)->get();
                $agency = Agency::where('name', $user->agn)->orWhere('id', $user->agn)->get();
                if ($branch->count() > 0) {
                    $user->brn = $branch->first()->brn_id;
                }
                if ($agency->count() > 0) {
                    $user->agn = $agency->first()->agn_id;
                }
                $user->save();
                echo "User {$user->id} updated\n";
            }
        } catch (\Throwable $th) {
            //throw $th;
            echo "Error: {$th->getMessage()}\n";
        } finally {
            echo "........... Done ............\n";
        }
    }
}
