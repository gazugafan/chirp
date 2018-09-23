<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ClearFollowCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'clear:to_follow';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Clears the list of users you want to follow';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		\Storage::delete('to_follow');
		$this->info('To-follow list cleared successfully!');
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
