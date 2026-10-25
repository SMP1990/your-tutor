<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Makes an admin account from the server's command line (Step 7a). The website itself can never make
 * an admin, so this is the only way to get the first one. The password is typed hidden, never as an argument.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
        {email : Login email of the new admin}
        {--name= : Full name (asked if left out)}
        {--phone= : Pakistani mobile number (optional)}';

    protected $description = 'Create an admin account (password is asked privately)';

    public function handle(): int
    {
        $name = trim((string) ($this->option('name') ?: $this->ask('Full name')));
        $password = (string) $this->secret('Password (at least 12 characters, with letters and numbers)');
        $again = (string) $this->secret('Password again');

        $validator = Validator::make([
            'name' => $name,
            'email' => mb_strtolower(trim((string) $this->argument('email'))),
            'phone' => preg_replace('/[\s-]/', '', (string) $this->option('phone')) ?: null,
            'password' => $password,
            'password_confirmation' => $again,
        ], [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['nullable', 'regex:/^(\+92|0)3\d{9}$/'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();

        $admin = new User(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'password' => $data['password']]);
        $admin->role = Role::Admin; // never mass-assigned
        $admin->email_verified_at = now(); // an admin made on the server needs no verification email
        $admin->save();

        $this->info("Admin created: {$admin->name} <{$admin->email}>. Log in at /login; the admin code is sent by email.");

        return self::SUCCESS;
    }
}
