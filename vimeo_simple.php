<?php

/**
 * VimeoSimple class
 *
 * This source file can be used to communicate with Vimeo (http://vimeo.com)
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them. Reporting can be done by sending an email to php-vimeo-simple-bugs[at]verkoyen[dot]eu.
 * If you report a bug, make sure you give me enough information (include your code).
 *
 * License
 * Copyright (c), Tijs Verkoyen. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author			Tijs Verkoyen <php-vimeo-simple@verkoyen.eu>
 * @version			1.0.0
 *
 * @copyright		Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license			BSD License
 */
class VimeoSimple
{
	// internal constant to enable/disable debugging
	const DEBUG = false;

	// url for the vimeo-api
	const API_URL = 'http://vimeo.com/api/v2';

	// port for the vimeo-API
	const API_PORT = 80;

	// current version
	const VERSION = '1.0.0';


	/**
	 * cURL-instance
	 *
	 * @var	resource
	 */
	private $curl;


	/**
	 * The timeout
	 *
	 * @var	int
	 */
	private $timeOut = 60;


	/**
	 * The user agent
	 *
	 * @var	string
	 */
	private $userAgent;


// class methods
	/**
	 * Default constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
	}


	/**
	 * Default destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		// close
		if($this->curl !== null) curl_close($this->curl);
	}


	/**
	 * Make the call
	 *
	 * @return	string
	 * @param	string $url						The URL to call.
	 * @param	array[optional] $parameters		The parameters to pass.
	 */
	private function doCall($url, array $parameters = null)
	{
		// redefine
		$url = (string) $url;

		// init var
		$queryString = '';

		// any parameters
		if(!empty($parameters))
		{
			// build query string
			$queryString = http_build_query($parameters);

			// append to url
			$url .= '?' . $queryString;
		}

		// prepend
		$url = self::API_URL . '/' . $url;

		// set options
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_PORT] = self::API_PORT;
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		if(ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) $options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_TIMEOUT] = (int) $this->getTimeOut();

		// init
		if($this->curl === null) $this->curl = curl_init();

		// set options
		curl_setopt_array($this->curl, $options);

		// execute
		$response = curl_exec($this->curl);
		$headers = curl_getinfo($this->curl);

		// fetch errors
		$errorNumber = curl_errno($this->curl);
		$errorMessage = curl_error($this->curl);

		// invalid headers
		if(!in_array($headers['http_code'], array(0, 200)))
		{
			// should we provide debug information
			if(self::DEBUG)
			{
				// make it output proper
				echo '<pre>';

				// dump the header-information
				var_dump($headers);

				// dump the raw response
				var_dump($response);

				// end proper format
				echo '</pre>';
			}

			// throw error
			throw new VimeoSimpleException('Invalid headers (' . $headers['http_code'] . ')', (int) $headers['http_code']);
		}

		// error?
		if($errorNumber != '') throw new VimeoSimpleException($errorMessage, $errorNumber);

		// we expect JSON so decode it
		$json = @json_decode($response, true);

		// validate json
		if($json === false) throw new VimeoSimpleException('Invalid JSON-response');

		// return
		return $json;
	}


	/**
	 * Get the timeout that will be used
	 *
	 * @return	int
	 */
	public function getTimeOut()
	{
		return (int) $this->timeOut;
	}


	/**
	 * Get the useragent that will be used. Our version will be prepended to yours.
	 * It will look like: "PHP VimeoSimple/<version> <your-user-agent>"
	 *
	 * @return	string
	 */
	public function getUserAgent()
	{
		return (string) 'PHP VimeoSimple/' . self::VERSION . ' ' . $this->userAgent;
	}


	/**
	 * Set the timeout
	 * After this time the request will stop. You should handle any errors triggered by this.
	 *
	 * @return	void
	 * @param	int $seconds	The timeout in seconds.
	 */
	public function setTimeOut($seconds)
	{
		$this->timeOut = (int) $seconds;
	}


	/**
	 * Set the user-agent for you application
	 * It will be appended to ours, the result will look like: "PHP VimeoSimple/<version> <your-user-agent>"
	 *
	 * @return	void
	 * @param	string $userAgent	Your user-agent, it should look like <app-name>/<app-version>.
	 */
	public function setUserAgent($userAgent)
	{
		$this->userAgent = (string) $userAgent;
	}


// user requests
	/**
	 * Get info about the specified user
	 *
	 * @return	array
	 * @param	string $username	Either the shortcut URL or ID of the user, an email address will NOT work.
	 */
	public function getUserInfo($username)
	{
		// make the call
		return $this->doCall((string) $username . '/info.json');
	}


	/**
	 * Get videos created by the user
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getUserVideos($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall((string) $username . '/videos.json', $parameters);
	}


	/**
	 * Get videos the user likes
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getUserLikes($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall((string) $username . '/likes.json', $parameters);
	}


	/**
	 * Get videos that the user appears in
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getUserAppearsIn($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall((string) $username . '/appears_in.json', $parameters);
	}


	/**
	 * Get videos that the user appears in and created
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getUserAllVideos($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall((string) $username . '/all_videos.json', $parameters);
	}


	/**
	 * Get videos the user is subscribed to
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getUserSubscriptions($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall((string) $username . '/subscriptions.json', $parameters);
	}


	/**
	 * Get albums the user has created
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getUserAlbums($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall((string) $username . '/albums.json', $parameters);
	}


	/**
	 * Get the channels the user has created and subscribed to
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getUserChannels($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall((string) $username . '/channels.json', $parameters);
	}


	/**
	 * Get groups the user has created and joined
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getUserGroups($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall((string) $username . '/groups.json', $parameters);
	}


	/**
	 * Get the videos the user's contacts created
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getUserContactVideos($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall((string) $username . '/contacts_videos.json', $parameters);
	}


	/**
	 * Get the videos the user's contacts like
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getUserContactLikes($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall((string) $username . '/contacts_like.json', $parameters);
	}


// video requests
	/**
	 * Get the data about a specific video
	 *
	 * @return	array
	 * @param	string $id	The ID of the video you want information for.
	 */
	public function getVideoInfo($id)
	{
		// make the call
		return $this->doCall('video/' . (string) $id . '.json');
	}


// activity requests
	/**
	 * Get activities by the user
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getActivityUserDid($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall('activity/' . (string) $username . '/user_did.json', $parameters);
	}


	/**
	 * Get activities on the user
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getActivityHappenedToUser($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall('activity/' . (string) $username . '/happened_to_user.json', $parameters);
	}


	/**
	 * Get activities by the user's contacts
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getActivityContactsDid($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall('activity/' . (string) $username . '/contacts_did.json', $parameters);
	}


	/**
	 * Get activities on the user's contacts
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getActivityHappenedToContacts($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall('activity/' . (string) $username . '/happened_to_contacts.json', $parameters);
	}


	/**
	 * Get activities for everyone
	 *
	 * @return	array
	 * @param	string $username		Either the shortcut URL or ID of the user, an email address will NOT work.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getActivityEveryoneDid($username, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall('activity/' . (string) $username . '/everyone_did.json', $parameters);
	}


// group requests
	/**
	 * Get info for the specified group
	 *
	 * @return	array
	 * @param	string $groupname	Either the shortcut URL or ID of the group.
	 */
	public function getGroupInfo($groupname)
	{
		// make the call
		return $this->doCall('group/' . (string) $groupname . '/info.json');
	}


	/**
	 * Get users who have joined the group
	 *
	 * @return	array
	 * @param	string $groupname		Either the shortcut URL or ID of the group.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getGroupUsers($groupname, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall('group/' . (string) $groupname . '/users.json', $parameters);
	}


	/**
	 * Get videos added to that group
	 *
	 * @return	array
	 * @param	string $groupname		Either the shortcut URL or ID of the group.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getGroupVideos($groupname, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall('group/' . (string) $groupname . '/videos.json', $parameters);
	}


// channel requests
	/**
	 * Get channel info for the specified channel
	 *
	 * @return	array
	 * @param	string $channelname		Either the shortcut URL of the channel.
	 */
	public function getChannelInfo($channelname)
	{
		// make the call
		return $this->doCall('channel/' . (string) $channelname . '/info.json');
	}


	/**
	 * Get videos in the channel
	 *
	 * @return	array
	 * @param	string $channelname		Either the shortcut URL of the channel.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getChannelVideos($channelname, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall('channel/' . (string) $channelname . '/videos.json', $parameters);
	}


// album requests
	/**
	 * Get info for the specified album
	 *
	 * @return	array
	 * @param	string $albumId		The ID of the album.
	 */
	public function getAlbumInfo($albumId)
	{
		// make the call
		return $this->doCall('album/' . (string) $albumId . '/info.json');
	}


	/**
	 * Get videos in that album
	 *
	 * @return	array
	 * @param	string $albumId			The ID of the album.
	 * @param	int[optional] $page		The page to retrieve.
	 */
	public function getAlbumVideos($albumId, $page = null)
	{
		// build parameters
		$parameters = null;
		if($page !== null) $parameters['page'] = (int) $page;

		// make the call
		return $this->doCall('album/' . (string) $albumId . '/videos.json', $parameters);
	}
}


/**
 * VimeoSimple Exception class
 *
 * @author	Tijs Verkoyen <php-vimeo-simple@verkoyen.eu>
 */
class VimeoSimpleException extends Exception
{
}

?>