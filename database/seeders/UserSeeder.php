<?php

namespace Database\Seeders;

use App\Enums\Role\DefaultRoleNamesEnum;
use App\Models\Role;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Prettus\Validator\Exceptions\ValidatorException;

class UserSeeder extends Seeder
{
    public const ADMIN_USERS = [
        [
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => '12345678',
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     *
     * @throws ValidatorException
     */
    public function run()
    {
    }
}
