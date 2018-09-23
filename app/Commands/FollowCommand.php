<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Library\Twitter;

class FollowCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'process:follow';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Follows (and mutes) 1 random user from the list of users you want to follow';

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
				//follow the user...
				$userID = reset($userIDs);
				$userData = $twitter->mute_user($userID);
				$userData = $twitter->follow_user($userID);

				//remove the user from our list of users to follow...
				$twitter->remove_user_to_follow($userID);

				//add the user to our list of followed users (to potentially be unfollowed later)...
				$twitter->add_followed_user($userID);

				$this->info('Followed ' . $userData['screen_name'] . '!');
			}
			else
				$this->warn("There aren't any users waiting to be followed");
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
				$twitter->remove_user_to_follow($userID);
				$this->error('Removed user #' . $userID . ' from our list of users to follow, since following them doesn\'t seem to be working');
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
