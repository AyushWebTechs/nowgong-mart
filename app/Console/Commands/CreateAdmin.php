<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'mart:create-admin
        {--name= : Owner name (skips the prompt)}
        {--phone= : Mobile number / login ID (skips the prompt)}
        {--password= : Password (skips both password prompts; for unattended deploys)}';

    protected $description = 'Create (or promote) the store owner admin account';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?? $this->ask('Owner name'),
            'phone' => $this->option('phone') ?? $this->ask('Mobile number (login ID, 10 digits)'),
            'password' => $this->option('password') ?? $this->secret('Password (min. 12 characters)'),
        ];
        $data['password_confirmation'] = $this->option('password') ?? $this->secret('Repeat password');

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::firstOrNew(['phone' => $data['phone']]);
        $user->fill(['name' => $data['name'], 'password' => $data['password']]);
        $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

        $this->info("Admin account ready for {$user->phone}. Log in at ".url('/login'));

        return self::SUCCESS;
    }
}
