<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Library\Twitter;

class ListFollowedCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'list:followed';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Lists the user IDs we\'ve followed and may still unfollow later';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Twitter $twitter)
    {
		try
		{
			$users = $twitter->get_followed_users();
			if (count($users) > 0)
			{
				foreach($users as $user)
					$this->line($user->userID . ' (expires ' . \Carbon\Carbon::createFromTimestamp($user->followed, 'UTC')->addDays(config('app.days_to_wait_for_followback'))->format('l, M d, Y @ h:ia T') . ')');
			}
			else
				$this->warn("There are no users we might unfollow. Add some using the add:user command.");
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
