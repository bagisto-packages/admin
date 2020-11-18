<?php

namespace BagistoPackages\Admin\Seeds;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('admins')->delete();

        DB::table('roles')->delete();

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'Administrator',
            'description' => 'Administrator role',
            'permission_type' => 'all',
        ]);
    }
}
