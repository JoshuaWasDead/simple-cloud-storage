<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //Если нет админа, созщ
        if (!$admin = User::find(1)) {
            $admin = new User;
            $admin->id = 1;
            $admin->name = 'Admin';
            $admin->favorite_colour = 'pink';
            $admin->email = Config::get('auth.admin_email');
            $admin->password = Hash::make(Config::get('auth.admin_password'));
        }
        $admin->save();
        if (!($admin->tokens()->exists())) {
            $admin->createToken('api-access', ['create-users', 'access-sensitive-info']);
        }
    }
}
