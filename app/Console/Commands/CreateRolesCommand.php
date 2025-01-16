<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class CreateRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'An artisan commands to create a user roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Role::create(['name' => 'SUPERADMIN', 'guard_name' => 'web']);

        Role::create(['name' => 'ADMIN', 'guard_name' => 'web']);

        Role::create(['name' => 'STAFF', 'guard_name' => 'web']);

        Role::create(['name' => 'EDITOR', 'guard_name' => 'web']);
        
        Role::create(['name' => 'DEPARTMENT_HEAD', 'guard_name' => 'web']);
        
        Role::create(['name' => 'USER', 'guard_name' => 'web']);

        $this->info('ADMIN, STAFF AND EDITOR roles have been successfully created!');    
    }
}
