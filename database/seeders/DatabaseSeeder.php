<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
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
        //Если +
        if (!$admin = User::find(1)) {
            $admin = new User;
            $admin->id = 1;
            $admin->name = 'Admin';
            $admin->favorite_colour = 'pink';
            $admin->email = 'test@example.com';
            $admin->password = Hash::make(env('ADMIN_DEFAULT_PASSWORD'));
        }
        $admin->save();
        if (!($admin->tokens()->exists())) {
            $admin->createToken('api-access', ['create-users'])->plainTextToken;
        }
    }
}
