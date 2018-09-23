# Chirp

A command-line tool to automatically follow Twitter users and later unfollow them if they don't follow you back. Use it to easily grow your Twitter audience.

## Requirements
* PHP 7.1.3+
* Composer

## Installation
* Clone this repository on your server
* Run `composer install`
* Run `php chirp` to test that it's working and see a list of commands
* Add the following cron task to run every minute (the actual schedule is managed in the app)...
```
* * * * * php /INSTALL/PATH/chirp schedule:run >> /dev/null 2>&1
```

## Setup
* You'll need to have a Twitter app created, along with a personal access token to authenticate to the Twitter API. You might be able to do this from [the Twitter app manager](https://apps.twitter.com/), but this is being deprecated soon. If you can't do it there, you'll probably need to [apply for a developer account](https://developer.twitter.com).
* Rename `.env.example` to `.env`
* Open `.env` and fill in the OAuth details from the Twitter app you created. You'll need the app's consumer key and secret, along with a personal access token and secret.
* Run `php chirp test:twitter` to test that we can authenticate to the Twitter API.

## Usage
Run `php chirp add:user <username>` to add the followers of the specified user to a locally stored list. Do this as many times as you'd like to grow this list of users. Duplicates are automatically removed, along with users you already follow.

After this, chirp will periodically follow and mute a random user from this list. Muted users don't appear in your timeline, but otherwise act as normal follows. The hope is that some of these users will follow you back soon! If they do, consider unmuting them if you'd like to see their tweets.

Chirp keeps track of who it's followed for you, along with when it followed them. If one of these users hasn't followed you back after 10 days, chirp will eventually unfollow them.

## Rate Limits
Out of the box, chirp is not scheduled to run fast enough to come close to hitting any rate limits. However, you can modify this schedule to run as fast as you'd like. In this case, chirp attempts to proactively respects Twitter's rate limits.

Some of Twitter's API methods always respond with rate limit data--telling us how many requests you have remaining and when you'll get more. For these API methods, chirp remembers the rate-limit information and will not attempt an API request if you have no more attempts remaining. Chirp does NOT simply make API requests until the API starts responding with rate limit errors. It knows to stop ahead of time, and it knows when it can start again.

There are other Twitter API methods that do NOT respond with rate limit data. These include following users, muting users, and unfollowing users. Twitter is a little ambiguous about the rate limits on these, but from what I can tell they each have their own 1000 daily request limit. By default, chirp is configured to proactively limit these requests to 990 per day to be sure to stay under. You can adjust this in `config/app.php`.

In any case, if Twitter ever responds with a rate limit error, chirp will hold off on that type of request for 24 hours just to be safe.

## Schedule
By default, chirp is scheduled to run once every 5 minutes on weekdays between 10am-4pm CST. To make things a little fuzzy, chirp will wait a random number of seconds (up to 250, or about 4 minutes) everytime it runs. Each time chirp runs, it will...

1) Attempt to follow 1 random user from your list of users to follow, and
2) Attempt to unfollow 1 random user from the list of users chirp followed over 10 days ago who have not followed you back. (you can change how many days to wait for a follow back in `config/app.php`)

You can modify this schedule in `app/Commands/WorkCommand.php`. To make it go really fast, you can have each run perform multiple follows/unfollows. See the end of the file for more information.

## Log
You can review the log of what chirp did when it was running on schedule in the `chirp.log` file.

## Other Commands
The main command you'll use is `add:user`, which adds the followers of a user to the list of users chirp will attempt to follow. If you'd like, you can review this list using the `list:to_follow` command (though it lists user IDs--not screen names).

Once you've setup the cron, chirp will run the `process:work` command on schedule, which attempts to follow 1 user and unfollow 1 user.

You can also manually follow a user from the list of users you want to follow using the `process:follow` command. Likewise, you can manually unfollow a user who hasn't followed you back using the `process:unfollow` command.

To see all of the available commands, run `php chirp`. To get more help on a specific command, run `php chirp help <command>`. For example: `php chirp help add:user` will tell you how to use the `add:user` command.

## Storage
Chirp just uses simple local file storage to remember who it has followed and such--no database server required! These files are located in the `storage` folder.