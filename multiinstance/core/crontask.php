<?php

/**
* ownCloud - App Template Example
*
* @author Bernhard Posselt
* @copyright 2012 Bernhard Posselt nukeawhale@gmail.com 
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\MultiInstance\Core;


class CronTask {
	

	private $api; 
	private $receivedUserMapper;
	private $userUpdateMapper;
	private $receivedFriendshipMapper;
	private $receivedUserFacebookIdMapper;
	private $locationMapper;

	private $dbuser;
	private $dbpassword;
	private $dbname;
	private $dbtableprefix;

	private $recvPathPrefix;
	private $sendPathPrefix;
	
	private static $tables = array(
		'multiinstance_queued_users' => 'multiinstance_received_users',
		'multiinstance_queued_friendships' => 'multiinstance_received_friendships',
		'multiinstance_queued_user_facebook_ids' => 'multiinstance_received_user_facebook_ids', 
		'multiinstance_requests' => 'multiinstance_received_requests'
	);
	
	private static $patterns = array(
		'multiinstance_queued_users.sql' => '/^INSERT.*VALUES \((?<uid>[^,]+),[^,]*,[^,]*,(?<timestamp>[^,]+)\)$/',
		'multiinstance_queued_friendships.sql' =>'/^INSERT.*VALUES \((?<friend_uid1>[^,]+),(?<friend_uid2>[^,]+),(?<timestamp>[^,]+),\d\)$/',  
		'multiinstance_queued_user_facebook_ids.sql' =>  '/^INSERT.*VALUES \((?<uid>[^,]+),[^,]*,[^,]*,(?<timestamp>[^,]+)\)$/' 
	);

	/**
	 * @param API $api: an api wrapper instance
	 */
	public function __construct($api, $receivedUserMapper, $userUpdateMapper, $locationMapper, $receivedFriendshipMapper, $receivedUserFacebookIdMapper){
		$this->api = $api;
		$this->receivedUserMapper = $receivedUserMapper;
		$this->userUpdateMapper = $userUpdateMapper;
		$this->receivedFriendshipMapper = $receivedFriendshipMapper;
		$this->receivedUserFacebookIdMapper = $receivedUserFacebookIdMapper;
		$this->locationMapper = $locationMapper;

		$this->dbuser = $this->api->getSystemValue('dbuser'); 
		$this->dbpassword = $this->api->getSystemValue('dbpassword'); 
		$this->dbname = $this->api->getSystemValue('dbname'); 
		$this->dbtableprefix = $this->api->getSystemValue('dbtableprefix');
		$this->recvPathPrefix = $this->api->getAppValue('dbSyncRecvPath'); 
		$this->sendPathPrefix = $this->api->getAppValue('dbSyncPath');


	}

	/**
	 * Dumps all the Queued<object> tables into files in the db_sync directory.
	 * Other code containing rsync commands will sync these files.  Deleted by
	 * other code on a time interval
	 */
	public function dumpQueued() {
		//TODO need to update for each location
		foreach (self::$tables as $queuedTable => $receivedTable) {
			$qTable = $this->dbtableprefix  . $queuedTable;
			$rTable = $this->dbtableprefix . $receivedTable;
			foreach ($locationMapper->findAll() as $location) {
				if (strpos($location->getLocation(), ";") !== false) {
					$this->api->log("Location {$location->getLocation()} has a semicolon in it.  This is not allowed.");
					continue;
				}
				$file = "{$this->sendPathPrefix}{$location->getLocation()}/{$queuedTable}.sql";

				$cmd = "mysqldump --add-locks --insert  --skip-comments --skip-extended-insert --no-create-info --no-create-db -u{$this->dbuser} -p{$this->dbpassword} {$this->dbname} {$qTable} --where=\"location='{$location->getLocation}'\" > {$file}";
				$escaped_command = escapeshellcmd($cmd); //escape since input is taken from config/conf.php
				$this->api->exec($escaped_cmd);
				$replace = "sed -i 's/{$qTable}/{$rTable}/g' {$file}";
				$this->api->exec(escapeshellcmd($replace));
				$eof = "sed -i '1i-- done;' {$file}";
				$this->api->exec($eof);
		}
	}

	/**
	 * Dumps all Responses for each location into their folder of the db_sync
	 * directory.  Responses are accumulated and dumped because in order to
	 * reduce how often sync is necessary.
	 */
	public function dumpResponses() {
		$queuedTable = $this->dbtableprefix . "responses";
		$receivedTable = $this->dbtableprefix . "received_responses";

		foreach ($locationMapper->findAll() as $location) {
			if (strpos($location->getLocation(), ";") !== false) {
				$this->api->log("Location {$location->getLocation()} has a semicolon in it.  This is not allowed.");
				continue;
			}
			$file = "{$this->sendPathPrefix}{$location->getLocation()}/r{$this->api->microTime()}";

			$cmd = "mysqldump --add-locks --insert --skip-comments --no-create-info --no-create-db -u{$this->dbuser} -p{$this->dbpassword} {$this->dbname} {$queuedTable} --where=\"location='{$location->getLocation()}'\" > {$file}";
			$escaped_command = escapeshellcmd($cmd);
			$this->api->exec($escaped_command);
			$replace = "sed -i 's/{$queuedTable}/{$receivedTable}/g' {$file}";
			$this->api->exec(escapeshellcmd($replace));
			$eof = "sed -i '1i-- done;' {$file}";
			$this->api->exec($eof);
		}
	}

	/**
	 * Executes the dumped Queued<object> scripts to put the received rows
	 * into Received<object>.
	 */
	public function insertReceived() {
		$dirs = $this->api->glob($this->recvPathPrefix . "*", true );

		foreach ($dirs as $dir){
			$locationName = $this->api->baseName($dir);	
			$ip = $this->locationMapper->findIPByLocation($locationName);
			if ($ip === null) {
				throw new \Exception("Location {$dir} does not have an IP address.");
			}
			foreach (self::$tables as $queuedTable => $receivedTable) {
				$full_file =  "{$dir}/{$queuedTable}.sql";
				if(!$this->api->fileExists($full_file)) {
					continue;
				}
				$this->mysqlExecuteFile($full_file, $locationName);
			}
		}
	}


	/**
	 * readAcks processes all unread acknowledgements by executing their contents
	 * which will delete the acknowledged Queued<object> entries.  Acknowledments are
	 * all files in the db_sync_recv folder which are in the format "a<timestamp-as-a-float>".
	 * These acknowledement files will be deleted by other code on a time interval.
	 */
	public function readAcksAndResponses() {
		$dirs = $this->api->glob($this->recvPathPrefix . "*", true );

		foreach ($dirs as $dir){
			$files = $this->api->glob($dir . "/a*");
			//Only non-central server should process responses
			if ($api->getSystemValue('centralServer') !== $api->getSystemValue('location')) {
				$files2 = $this->api->glob($dir . "/r*");
				$files = array_merge($files, $files2);
			}
			$lastReadFilename = "{$dir}/last_read.txt";  
			$lastUpdatedFilename = "{$dir}/last_updated.txt";
			$lastReadStringTime = $this->api->fileGetContents($lastReadFilename);  //this should be in microTime format
			if ($lastReadStringTime === false) {
				$this->api->log("last_read.txt for {$dir} cannot be read.  Using time 0");
				$lastReadStringTime = "0.0";
			}
			else if ($lastReadStringTime == "") {
				$this->api->log("last_read.txt for {$dir} does not have anything in it.  Using time 0");
				$lastReadStringTime = "0.0";
			}
			$lastReadTime = (float)$lastReadStringTime;
			
			$lastUpdatedStringTime = $this->api->fileGetContents($lastUpdatedFilename); //this should be in microTime format
			if ($lastUpdatedStringTime === false) {
				$this->api->log("last_updated.txt for {$dir} cannot be read.");
				continue;
			}
			if ($lastUpdatedStringTime == "") {
				$this->api->log("last_updated.txt for {$dir} does not have anything in it.");
				continue;
			}
			$lastUpdatedTime = (float)$lastUpdatedStringTime;

			foreach ($files as $file) {
				$filename = $this->api->baseName($file);
				$time = floatval(substr($filename,1)); //remove 'a' and get microTime
				if ($time == 0) {
					continue;
				}
				if ($lastReadTime < $time && $time <= $lastUpdatedTime) {
					$cmd = "mysql -u{$this->dbuser} -p{$this->dbpassword} {$this->dbname} < {$file}";
					$this->api->exec($cmd);
				}
			}
			if ($this->api->filePutContents($lastReadFilename, $lastUpdatedStringTime) === false) {
				$this->log("Error writing to 'last_read.txt' for {$dir}.");
			}
		}
	}


	//Copied from OCA\AppFramework\Db\Mapper for general query execution
	protected function execute($sql, array $params=array(), $limit=null, $offset=null){
		$query = $this->api->prepareQuery($sql); //PDO object
		return $query->execute($params);
	}

	/**
	 * Execute queries one at a time and generate an ack for each of them.
	 *
	 * source: http://stackoverflow.com/questions/7840044/how-to-execute-mysql-script-file-in-php
	 */
	protected function mysqlExecuteFile($filename, $locationName){
		$first = true;
		$ackedList = "";
		$filebase = $this->api->baseName($filename);
		$acks = $filebase !== "requests.sql" ? true : false //Don't want to delete with acknowledgement, want to delete with answer
		if ($file = $this->api->fileGetContents($filename)){
			foreach(explode(";", $file) as $query){
				$query = trim($query);
				if ($first) {
					//If still being written
					if ($query !== "-- done")
						return;
					$first = false;
					continue;
				}
				if ($acks) {
					$ackedList .= $this->toAckFormat($query, $filebase);
				}
				if (!empty($query) && $query !== ";") {
					$this->execute($query);
		    		}
			}
	    	}
		if ($acks && $ackedList !== "") {
			$this->ack($ackedList, $locationName);
		}
	}

	/**
	 * Return the ack (delete query) for a row
	 * @param $query string
	 */
	protected function toAckFormat($query, $filename) {
		$matches = array();
		
		if (array_key_exists($filename, self::$patterns) !== true) {
			throw new \Exception("No pattern for sql file {$filename}");
		}
		$pattern = self::$patterns[$filename];
		preg_match($pattern, $query, $matches);
		switch ($filename) {
			case 'multiinstance_queued_users.sql':
				if (sizeof($matches) < 3) {
					$formattedQuery = "";
				}
				else {			
					$formattedQuery = $this->deleteQueuedUserSql($matches['uid'], $matches['timestamp']) . ";\n";
				}
				break;
			case 'multiinstance_queued_friendships.sql':
				if (sizeof($matches) < 4) {
					$formattedQuery = "";
				}
				else {
					$formattedQuery = $this->deleteQueuedFriendshipSql($matches['friend_uid1'], $matches['friend_uid2'], $matches['timestamp']) . ";\n";
				}
				break;
			case 'multiinstance_queued_user_facebook_ids.sql':
				if (sizeof($matches) < 3) {
					$formattedQuery = "";
				}
				else {
					$formattedQuery = $this->deleteQueuedUserFacebookIdSql($matches['uid'], $matches['timestamp']) . ";\n";
				}		
				break;
			default:
				throw new \Exception("No delete query function for {$filename}");

		}
		return $formattedQuery;
	}

	/**
	 * Writes out the acknowlegements to a file in db_sync to be synced
	 * back to the sending village.
	 * @param $ackedList string
	 * @param $ip string - IP of the village to send the ack back to
	 */
	protected function ack($ackedList, $locationName){
		$time = $this->api->microTime();
		$filename = "{$this->sendPathPrefix}{$locationName}/a{$time}";
		$cmd = "echo \"{$ackedList}\" >> {$filename}";
		$this->api->exec($cmd);
	}

	/**
	 * Should be called UCSB side only
	 */
	public function processRequests() {
		$receivedRequests = $this->receivedRequestMapper->findAll();
		foreach ($receivedRequests as $receivedRequest) {
			$id = $receivedRequest->getId();
			$sendingLocation = $receivedRequest->getSendingLocation();
			$type = $receivedRequest->getType();
			$addedAt = $receivedRequest->getAddedAt();
			$field1 = $receivedRequest->getField1();
			
			switch ($type) {
				case Request::USER_EXISTS: //Want same behavior for these two queries
				case Request::FETCH_USER: //for login for a user that doesn't exist in the db
					$userExists = $this->api->userExists($field1);	

					$this->api->beginTransaction();
					$response = new QueuedResponse($id, $sendingLocation, (string) $userExists);
					$this->queuedResponseMapper->save($response); //Does not throw Exception if already exists

					$userUpdate = $this->userUpdateMapper->find($uid);
					$displayName = $this->api->getDisplayName($uid);
					$password = $this->api->getPassword($uid);
					$queuedUser = new QueuedUser($uid, $displayName, $password, $userUpdate->updatedAt(), $sendingLocation); 
					$this->queuedUserMapper->save($queuedUser); //Does not throw Exception if already exists
					$this->api->commit();
					
					break;
				default:
					throw \Exception("Invalid request_type {$type} for request from {$location} added_at {$addedAt)}, field1 = {$field1}";
					break;
			}

			$request = $this->receiveRequestMapper->delete($id);
		}
	}


	/**
	 * Should be called Village side only
	 */
	public function processResponses() {
		$receivedResponses = $this->receivedResponsesMapper->findAll();
		foreach ($receivedResponses as $receivedResponse) {
			$requestId = $receivedResponse->getRequestId();

			$queuedRequest = $this->queuedRequest->find($requestId); 
			$addedAt = $receivedResponse->getAddedAt();
			$field1 = $receivedResponse->getField1();
			$answer = $receivedResponse->getAnswer();

			switch ($type) {
				case Request::USER_EXISTS:
					if ($answer !== "true" AND $answer !== "false") {
						$this->api->log("ReceivedResponse for Request USER_EXISTS, request_id = {$receivedResponse->getId()} had invalid response = {$answer}"); 
						continue;
					}
					if ($answer === "false") {
						$friendshipRequests = $this->friendshipMapper->findAllRecipientFriendshipRequestsByUser($field1);
						foreach ($friendshipRequests as $friendshipRequest) {
							$this->friendshipMapper->delete($friendshipRequest-getUid1(), $friendshipRequest->getUid2());
						}
					}
					$this->api->beginTransaction();
					//Don't need destination for delete since they should all be this instance
					$this->receivedResponseMapper->delete($requestId);
 					$this->queuedRequestMapper->delete($requestId);
					$this->api->commit();
					break;
				case Request::FETCH_USER: 
					if ($answer !== "true" AND $answer !== "false") {
						$this->api->log("ReceivedResponse for Request FETCH_USER, request_id = {$receivedResponse->getId()} had invalid response = {$answer}"); 
						continue;
					}

					$this->api->beginTransaction();
					//Don't need destination for delete since they should all be this instance
					$this->receivedResponseMapper->delete($requestId);
 					$this->queuedRequestMapper->delete($requestId);
					$this->api->commit();
					
					break;	
				default:
					$this->api->log("Invalid request_type {$type} for request id {$requestId} from {$location} added_at {$addedAt)}, field1 = {$field1}");
					continue;
					break;
			}
		}
	}


/* Methods for ack content (delete queued rows) */

	protected function deleteQueuedUserSql($uid, $addedAt) {
		return "DELETE IGNORE FROM \`{$this->dbtableprefix}multiinstance_queued_users\` WHERE \`uid\` = {$uid} AND \`added_at\` = {$addedAt}";
	} 

	protected function deleteQueuedFriendshipSql($uid1, $uid2, $updatedAt) {
		return "DELETE IGNORE FROM \`{$this->dbtableprefix}multiinstance_queued_friendships\` WHERE \`friend_uid1\` = {$uid1}  AND \`friend_uid2\` = {$uid2} AND \`updated_at\` = {$updatedAt}";
	}
	
	protected function deleteQueuedUserFacebookIdSql($uid, $syncedAt) {
		return "DELETE IGNORE FROM \`{$this->dbtableprefix}multiinstance_queued_user_facebook_ids\` WHERE \`uid\` = {$uid} AND \`friends_synced_at\` = {$syncedAt}";
	}

/* End methods for ack content */

}
