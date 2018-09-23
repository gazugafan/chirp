<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ClearCacheCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'clear:cache';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Clears the daily cached list of your own followers and friends';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		\Storage::delete('followers');
		\Storage::delete('following');
		$this->info('Cached list of followers cleared successfully!');
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
