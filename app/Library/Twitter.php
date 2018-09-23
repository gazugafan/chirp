<?php
namespace App\Library;

use Carbon\Carbon;

class TwitterRateLimitException extends \Exception {};

class Twitter
{
	private $codebird;

	public function __construct(\Codebird\Codebird $codebird)
	{
		$this->codebird = $codebird;
	}

	/**
	 * Removes spaces and @ symbols from a username and returns it in lowercase
	 */
	private function clean_username($username)
	{
		return trim(strtolower($username), "@ ");
	}

	/**
	 * Gets all of the stored user IDs that you want to follow, randomized.
	 * Removes userIDs that you already follow.
	 * Duplicates are removed
	 *
	 * @return string[] An array of unique user IDs
	 */
	public function get_users_to_follow()
	{
		if (\Storage::exists('to_follow'))
		{
			$existingUserIDs = $this->get_following();
			$userIDs = explode("\n", \Storage::read('to_follow'));
			$results = array();
			foreach($userIDs as $userID)
			{
				if ($userID && !in_array($userID, $existingUserIDs))
					$results[] = $userID;
			}
			shuffle($results);
			return array_unique($results);
		}

		return array();
	}

	/**
	 * Adds new userIDs to be followed. Returns the number of user IDs added.
	 * Duplicates are removed
	 *
	 * @param string[] $newUserIDs An array of user IDs to add
	 * @return int The number of users added (taking into account duplicates that might have already existed)
	 */
	public function add_users_to_follow($newUserIDs)
	{
		$userIDs = $this->get_users_to_follow();
		$originalCount = count($userIDs);
		foreach($newUserIDs as $newUserID)
		{
			if ($newUserID)
				$userIDs[] = $newUserID;
		}

		if (\Storage::exists('to_follow'))
			\Storage::delete('to_follow');

		$userIDs = array_unique($userIDs);
		\Storage::write('to_follow', implode("\n", $userIDs));
		$userIDs = $this->get_users_to_follow();

		return count($userIDs) - $originalCount;
	}

	/**
	 * Removes a userID to be followed.
	 *
	 * @param int $removeUserID The user ID to remove
	 */
	public function remove_user_to_follow($removeUserID)
	{
		$userIDs = $this->get_users_to_follow();

		$newUserIDs = [];

		foreach($userIDs as $userID)
		{
			if ($userID != $removeUserID)
				$newUserIDs[] = $userID;
		}

		if (\Storage::exists('to_follow'))
			\Storage::delete('to_follow');

		$newUserIDs = array_unique($newUserIDs);
		\Storage::write('to_follow', implode("\n", $newUserIDs));
	}

	/**
	 * Gets all of the users that have been followed by this tool (stored locally)
	 * Results are returned in random order, with duplicates removed
	 * Each user will be an object with a userID and timestamp
	 *
	 * @return [] An array of users
	 */
	public function get_followed_users()
	{
		if (\Storage::exists('followed'))
		{
			$users = json_decode(\Storage::read('followed'));
			$uniqueUsers = [];
			foreach($users as $user)
			{
				$alreadyAdded = false;
				foreach($uniqueUsers as $uniqueUser)
				{
					if ($uniqueUser->userID == $user->userID)
					{
						$alreadyAdded = true;
						break;
					}
				}

				if (!$alreadyAdded)
					$uniqueUsers[] = $user;
			}

			shuffle($uniqueUsers);
			return $uniqueUsers;
		}

		return array();
	}

	/**
	 * Adds a new userID that has been followed.
	 * Also automatically logs the date of the follow.
	 *
	 * @param int $newUserID The user ID to add
	 */
	public function add_followed_user($newUserID)
	{
		$users = $this->get_followed_users();
		$users[] = [
			'userID'=>$newUserID,
			'followed'=>Carbon::now('UTC')->timestamp,
		];

		if (\Storage::exists('followed'))
			\Storage::delete('followed');

		\Storage::write('followed', json_encode($users));
	}

	/**
	 * Removes a user from our list of users that we have followed
	 *
	 * @param int $removeUserID The user ID to remove
	 */
	public function remove_followed_user($removeUserID)
	{
		$users = $this->get_followed_users();

		$newUsers = [];

		foreach($users as $user)
		{
			if ($user->userID != $removeUserID)
				$newUsers[] = $user;
		}

		if (\Storage::exists('followed'))
			\Storage::delete('followed');

		\Storage::write('followed', json_encode($newUsers));
	}

	/**
	 * Returns the userID of a random user that we have followed, but has not followed
	 * us back within a set amount of days.
	 * If there is no such user, null is returned
	 */
	public function get_user_to_unfollow()
	{
		$followers = $this->get_followers();

		//go through each user that we followed...
		$followed = $this->get_followed_users();
		foreach($followed as $user)
		{
			//if we followed this user over X days ago...
			if (Carbon::now('UTC')->subDays(config('app.days_to_wait_for_followback'))->timestamp > $user->followed)
			{
				//check to see if the user is following us...
				if (!in_array($user->userID, $followers))
				{
					return $user->userID;
				}
			}
		}

		return null;
	}

	/**
	 * Uses the Twitter API to get the userIDs of the users that follow us.
	 * The results are cached locally, and only retrieved from the API once per day.
	 *
	 * @return string[] and Array of user IDs
	 */
	public function get_followers()
	{
		if (\Storage::exists('followers'))
		{
			$cache = json_decode(\Storage::read('followers'));
			if (Carbon::now('UTC')->subDay()->timestamp < $cache->cached)
			{
				return $cache->followers;
			}

			\Storage::delete('followers');
		}

		$followers = $this->get_users_followers();
		$cache = [
			'followers'=>$followers,
			'cached'=>Carbon::now('UTC')->timestamp,
		];

		\Storage::write('followers', json_encode($cache));

		return $followers;
	}

	/**
	 * Uses the Twitter API to get the userIDs of the users that you follow.
	 * The results are cached locally, and only retrieved from the API once per day.
	 *
	 * @return string[] and Array of user IDs
	 */
	public function get_following()
	{
		if (\Storage::exists('following'))
		{
			$cache = json_decode(\Storage::read('following'));
			if (Carbon::now('UTC')->subDay()->timestamp < $cache->cached)
			{
				return $cache->following;
			}

			\Storage::delete('following');
		}

		$following = $this->get_users_following();
		$cache = [
			'following'=>$following,
			'cached'=>Carbon::now('UTC')->timestamp,
		];

		\Storage::write('following', json_encode($cache));

		return $following;
	}

	/**
	 * Uses the Twitter API to get the userIDs of the users that you (or the specified user) follow.
	 *
	 * @return string[] an Array of user IDs
	 */
	public function get_users_following($username = null)
	{
		$username = $this->clean_username($username);

		//get all following IDs, in pages...
		$returnArray = [];
		$cursor = false;
		do
		{
			$params = [];
			if ($username)
				$params['screen_name'] = $username;

			if ($cursor)
				$params['cursor'] = $cursor;

			$this->respect_rate_limit('friends_ids');
			$response = (array)$this->codebird->friends_ids($params);
			$this->update_rate_limit('friends_ids', $response);

			//add this page of IDs...
			foreach($response['ids'] as $id)
			{
				if ($id)
					$returnArray[] = (string)$id;
			}

			$cursor = $response['next_cursor_str'];

		} while($cursor);

		return array_unique($returnArray);
	}

	/**
	 * Uses the Twitter API to get the userIDs of the users that follow the specified user.
	 *
	 * @param string $username The user whose followers to get
	 * @return string[] an Array of user IDs
	 */
	public function get_users_followers($username = null)
	{
		$username = $this->clean_username($username);

		//get all follower IDs, in pages...
		$returnArray = [];
		$cursor = false;
		do
		{
			$params = [];
			if ($username)
				$params['screen_name'] = $username;

			if ($cursor)
				$params['cursor'] = $cursor;

			$this->respect_rate_limit('followers_ids');
			$response = (array)$this->codebird->followers_ids($params);
			$this->update_rate_limit('followers_ids', $response);

			//add this page of IDs...
			foreach($response['ids'] as $id)
			{
				if ($id)
					$returnArray[] = (string)$id;
			}

			$cursor = $response['next_cursor_str'];

		} while($cursor);

		return array_unique($returnArray);
	}

	/**
	* Uses the Twitter API to follow a user
	*
	* @param int $userID The ID of the user to follow
	* @return string[] The user that was just followed
	*/
	public function follow_user($userID)
	{
		if (!$userID)
			throw new \Exception('You must specify the user ID to follow');

		$params = [
			'user_id'=>$userID,
			'follow'=>false,
		];

		$this->respect_rate_limit('friendships_create');
		$response = (array)$this->codebird->friendships_create($params);
		$this->update_rate_limit('friendships_create', $response);

		return $response;
	}

	/**
	* Uses the Twitter API to mute a user
	*
	* @param int $userID The ID of the user to mute
	* @return string[] The user that was just followed
	*/
	public function mute_user($userID)
	{
		if (!$userID)
			throw new \Exception('You must specify the user ID to mute');

		$params = [
			'user_id'=>$userID,
			'follow'=>false,
		];

		$this->respect_rate_limit('mutes_users_create');
		$response = (array)$this->codebird->mutes_users_create($params);
		$this->update_rate_limit('mutes_users_create', $response);

		return $response;
	}

	/**
	* Uses the Twitter API to unfollow a user
	*
	* @param int $userID The ID of the user to unfollow
	* @return string[] The user that was just unfollowed
	*/
	public function unfollow_user($userID)
	{
		if (!$userID)
			throw new \Exception('You must specify the user ID to unfollow');

		$params = [
			'user_id'=>$userID,
		];

		$this->respect_rate_limit('friendships_destroy');
		$response = (array)$this->codebird->friendships_destroy($params);
		$this->update_rate_limit('friendships_destroy', $response);

		return $response;
	}

	/**
	* Tests to see if you can authenticate to the Twitter API.
	* Returns user's info if successful. throws an error if you can't
	*/
	public function test_api()
	{
		$response = (array)$this->codebird->account_settings();

		if ($response['httpstatus'] == 200)
		{
			return $response;
		}
		elseif (array_key_exists('errors', $response))
		{
			$errorMessages = '';
			foreach($response['errors'] as $error)
				$errorMessages .= '; ' . $error->message;

			throw new \Exception('Could not authenticate. Twitter responded with: ' . trim($errorMessages, '; '));
		}
		elseif (array_key_exists('error', $response))
			throw new \Exception('Could not authenticate. Twitter responded with: ' . $response['error']);
		else
			throw new \Exception('Could not authenticate. Twitter didn\'t response with any more information about the problem.');
	}

	/**
	 * Checks to see if we previously exhausted our rate limit for the specified method. If so, we just throw an error.
	 * We'll automatically clear an exhausted rate limit when the exhausted period has expired.
	 * Call this before calling the Twitter API to make sure we don't ever go over any of their rate limits.
	 */
	public function respect_rate_limit($method)
	{
		if (\Storage::exists('rate_' . $method))
		{
			$rateInfo = \Storage::read('rate_' . $method);
			if ($rateInfo)
			{
				$rateInfo = (array)json_decode($rateInfo);
				if (array_key_exists('remaining', $rateInfo))
				{
					if (((int)$rateInfo['remaining']) <= 0)
					{
						$currentTimestamp = Carbon::now('UTC')->timestamp;
						$resetTimestamp = Carbon::createFromTimestamp($rateInfo['reset'], 'UTC')->timestamp;

						if ($currentTimestamp <= $resetTimestamp)
							throw new TwitterRateLimitException('Respecting rate limit on the ' . $method . ' method. This will clear at ' . Carbon::createFromTimestamp($rateInfo['reset'], 'UTC')->format('l, M d, Y @ h:ia T'));
					}
				}
			}
		}
	}

	/**
	 * Takes a response from the twitter API and updates our local rate limit info.
	 * Call this immediately after calling a Twitter API method to make sure the response was OK, and to
	 * update our local rate limit info so we can respect it before calling the same API method next time.
	 *
	 * @param string $method The name of the twitter API method that was called
	 * @param array $response The response we got from calling this API method
	 * @param boolean $validate If true, we'll throw an appropriate error if the response wasn't a 200 OK.
	 */
	public function update_rate_limit($method, $response, $validate = true)
	{
		switch($method)
		{
			case 'friendships_create':
			case 'mutes_users_create':
			case 'friendships_destroy':
				if (\Storage::exists('rate_' . $method))
				{
					$rateInfo = (array)json_decode(\Storage::read('rate_' . $method));
					$currentTimestamp = Carbon::now('UTC')->timestamp;
					$resetTimestamp = Carbon::createFromTimestamp($rateInfo['reset'], 'UTC')->timestamp;

					if ($currentTimestamp > $resetTimestamp)
						$rateInfo['remaining'] = config('app.daily_follow_limit');
					else
						$rateInfo['remaining'] = ((int)$rateInfo['remaining'] - 1);

					if ((int)$rateInfo['remaining'] < 0)
						$rateInfo['remaining'] = '0';

					\Storage::delete('rate_' . $method);
				}
				else
				{
					$rateInfo = [
						'remaining'=>(int)config('app.daily_follow_limit'),
					];
				}

				if ($response['httpstatus'] == 400 || $response['httpstatus'] == 429)
					$rateInfo['remaining'] = '0';

				$rateInfo['reset'] = Carbon::today('UTC')->addDay()->timestamp;

				\Storage::write('rate_' . $method, json_encode($rateInfo));
				break;

			default:
				if (\Storage::exists('rate_' . $method))
					\Storage::delete('rate_' . $method);

				if (array_key_exists('rate', $response) && $response['rate'] != null)
				{
					\Storage::write('rate_' . $method, json_encode($response['rate']));
				}
				else
				{
					//if for some reason we didn't get rate info in the response, lets just set a 24-hour delay to be safe...
					\Storage::write('rate_' . $method, json_encode([
						'limit'=>'15',
						'remaining'=>'0',
						'reset'=>(string)Carbon::now('UTC')->addDay()->timestamp,
					]));
				}
		}

		//if requested, make sure this is a good response from twitter and throw an error if it wasn't...
		if ($validate)
		{
			if ($response['httpstatus'] == 200)
			{
				return;
			}
			elseif (array_key_exists('errors', $response))
			{
				$errorMessages = '';
				foreach($response['errors'] as $error)
					$errorMessages .= '; ' . $error->message;

				throw new \Exception('A call to the ' . $method . ' twitter API method returned with the following errors: ' . trim($errorMessages, '; '));
			}
			elseif (array_key_exists('error', $response))
				throw new \Exception('A call to the ' . $method . ' twitter API method returned with the following error: ' . $response['error']);
			else
				throw new \Exception('A call to the ' . $method . ' twitter API method returned with an unexplained error.');
		}
	}
}