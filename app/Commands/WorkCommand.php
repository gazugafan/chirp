<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Library\Twitter;

class WorkCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
	protected $signature = 'process:work
							{fuzz=0 : If specified, we\'ll wait up to this random number of seconds before each follow/unfollow }
							{count=1 : The number of follows/unfollows to attempt this time }';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Follows random users you want to follow, and unfollows random users who haven\'t followed back';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Twitter $twitter)
    {
		for($x = 0; $x < ($this->argument('count') * 1); $x++)
		{
			sleep(rand(0, $this->argument('fuzz') * 1));

			$this->line(\Carbon\Carbon::now()->format('Y-m-d H:i:s T') . ': Starting work...');

			try
			{
				$this->call('process:follow');
				$this->call('process:unfollow');
			}
			catch(\Exception $e)
			{
				$this->error($e->getMessage());
			}

			$this->line(\Carbon\Carbon::now()->format('Y-m-d H:i:s T') . ': Finished work!');
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
		/*
		For information on how to modify the schedule, see...
		https://laravel.com/docs/5.7/scheduling#defining-schedules

		If you do modify the schedule, be sure to update the '250' below
		as well. We'll wait a random number of seconds up to this number
		before attempting each follow/unfollow (to add some fuzziness
		to the schedule).

		For example, you could schedule this to run up to every minute
		and set the fuzziness to about 50 seconds. Just make sure the
		fuzziness is less than the scheduled run time, minus a few seconds
		to account for how long it might take to access the Twitter API.
		Otherwise, a run could end up overlapping into the next run. If
		this happens, nothing will blow up, but the next run will simply
		be skipped.

		To make it go even faster, schedule it to run every minute, but
		set the count to something higher than 1, so that multiple
		follows/unfollows are attempted every minute.

		No matter what, we'll always proactively respect Twitter's
		rate limits.
		*/
		$schedule->command(static::class, ['250', '1'])
			->withoutOverlapping()
			->appendOutputTo('chirp.log')
			->everyFiveMinutes()
			->weekdays()
			->timezone('US/Central')
			->between('10:00', '16:00')
			;
    }
}
