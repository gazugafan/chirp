<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Library\Twitter;

class UnfollowCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'process:unfollow';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Unfollows 1 random user that has not followed you back yet (that we followed)';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Twitter $twitter)
    {
		try
		{
			if ($userID = $twitter->get_user_to_unfollow())
			{
				//unfollow the user...
				$userData = $twitter->unfollow_user($userID);

				//remove the user from our list of users to unfollow...
				$twitter->remove_followed_user($userID);

				$this->info('Unfollowed ' . $userData['screen_name'] . '!');
			}
			else
				$this->warn("There aren't any users to unfollow");
		}
		catch(\TwitterRateLimitException $e)
		{
			$this->error($e->getMessage());
		}
		catch(\Exception $e)
		{
			$this->error($e->getMessage());
			if ($userID)
			{
				$twitter->remove_followed_user($userID);
				$this->error('Removed user #' . $userID . ' from our list of users to unfollow, since unfollowing them doesn\'t seem to be working');
			}
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
