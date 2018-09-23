<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Library\Twitter;

class ListToFollowCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'list:to_follow';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Lists the user IDs you\'ve added that we will attempt to follow (in random order)';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Twitter $twitter)
    {
		try
		{
			$userIDs = $twitter->get_users_to_follow();
			if (count($userIDs) > 0)
			{
				foreach($userIDs as $userID)
					$this->line($userID);
			}
			else
				$this->warn("There are no users we will try to follow. Add some using the add:user command.");
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
