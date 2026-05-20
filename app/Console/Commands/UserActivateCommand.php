<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserActivateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:activate {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually activate a user by verifying their email address';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");

            return;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info("User with email {$email} is already activated.");

            return;
        }

        $user->markEmailAsVerified();

        $this->info("User with email {$email} has been successfully activated.");
    }
}
