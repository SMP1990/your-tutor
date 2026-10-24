<?php

namespace App\Console\Commands;

use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Console\Command;

/** Before launch: deletes every sample tutor (and their account) created by DemoTutorsSeeder. */
class RemoveDemoContent extends Command
{
    protected $signature = 'demo:remove {--force : Do not ask for confirmation}';

    protected $description = 'Delete all demo tutors and their accounts';

    public function handle(): int
    {
        $userIds = TutorProfile::where('is_demo', true)->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->info('No demo tutors found.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete {$userIds->count()} demo tutors and their accounts?")) {
            return self::FAILURE;
        }

        User::whereIn('id', $userIds)->delete(); // tutor profiles and subject links are removed by cascade
        $this->info("Deleted {$userIds->count()} demo tutors.");

        return self::SUCCESS;
    }
}
