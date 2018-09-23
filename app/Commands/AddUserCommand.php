<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Library\Twitter;

class AddUserCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'add:user {username : The username of the user whose followers you want to follow}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Adds the followers of a user to the list of users you want to follow';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Twitter $twitter)
    {
		try
		{
			$userIDs = $twitter->get_users_followers($this->argument('username'));
			$added = $twitter->add_users_to_follow($userIDs);
			$this->info($added . ' users added successfully!');
		}
		catch(\Exception $e)
		{
			$this->error($e->getMessage());
		}
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
