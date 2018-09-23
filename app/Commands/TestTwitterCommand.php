<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Library\Twitter;

class TestTwitterCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'test:twitter';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Tests if you are successfully authenticating to the Twitter API service';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Twitter $twitter)
    {
		try
		{
			$userInfo = $twitter->test_api();
			if (array_key_exists('screen_name', $userInfo) && $userInfo['screen_name'] != '')
			{
				$this->info('Successfully authenticated as ' . $userInfo['screen_name'] . '!');
			}
			else
			{
				$this->error('You seem to be authenticating, but we can\'t find your screen name. This is unexpected. More info...');
				var_dump($userInfo);
			}
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
