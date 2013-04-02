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
		'multiinstance_queued_user_facebook_ids' => 'multiinstance_received_user_facebook_ids'
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
		foreach (self::$tables as $queuedTable => $receivedTable) {
			$qTable = $this->dbtableprefix  . $queuedTable;
			$rTable = $this->dbtableprefix . $receivedTable;
			$file = "/home/sjones/public_html/dev/apps/multiinstance/db_sync/{$queuedTable}.sql";
			
			$cmd = "mysqldump --add-locks --insert  --skip-comments --skip-extended-insert --no-create-info --no-create-db -u{$this->dbuser} -p{$this->dbpassword} {$this->dbname} {$qTable} > {$file}";
			$escaped_comannd = escapeshellcmd($cmd); //escape since input is taken from config/conf.php
			$this->api->exec($cmd);
			$replace = "sed -i 's/{$qTable}/{$rTable}/g' {$file}";
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
	 * processAcks processes all unread acknowledgements by executing their contents
	 * which will delete the acknowledged Queued<object> entries.  Acknowledments are
	 * all files in the db_sync_recv folder which are in the format "a<timestamp-as-a-float>".
	 * These acknowledement files will be deleted by other code on a time interval.
	 */
	public function processAcks() {
		$dirs = $this->api->glob($this->recvPathPrefix . "*", true );

		foreach ($dirs as $dir){
			$files = $this->api->glob($dir . "/a*");
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
	private function execute($sql, array $params=array(), $limit=null, $offset=null){
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
				$ackedList .= $this->toAckFormat($query, $filebase);
				if (!empty($query) && $query !== ";") {
					$this->execute($query);
		    		}
			}
	    	}
		if ($ackedList !== "") {
			$this->ack($ackedList, $locationName);
		}
	}

	/**
	 * Return the ack (delete query) for a row
	 * @param $query string
	 */
	public function toAckFormat($query, $filename) {
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
	public function ack($ackedList, $locationName){
		$time = $this->api->microTime();
		$filename = "{$this->sendPathPrefix}{$locationName}/a{$time}";
		$cmd = "echo \"{$ackedList}\" >> {$filename}";
		$this->api->exec($cmd);
	}

/* Methods for updating instance db rows based on received rows */

	public function updateUsersWithReceivedUsers() {
		$receivedUsers = $this->receivedUserMapper->findAll();		

		foreach ($receivedUsers as $receivedUser){
			$id = $receiverUser->getUid();
			$receivedTimestamp = $receivedUser->getUpdatedAt();

			if ($this->api->userExists($id)) {
				$this->api->beginTransaction();

				//TODO: All of this should be wrapped in a try block with a rollback...
				$userUpdate = $this->userUpdateMapper->find($id);	
				//if this is new
				if ($receivedTimestamp > $userUpdate->getUpdatedAt()) {
					$userUpdate->setUpdatedAt($receivedTimestamp);	
					$this->userUpdateMapper->update($userUpdate);
					OC_User::setPassword($id, $receivedUser->getPassword());
					OC_User::setDisplayName($id, $receivedUser->getDisplayname());
					
				}
				$this->receivedUserMapper->delete($id, $receivedTimestamp);

				$this->api->commit();
			}
			else {
				$userUpdate = new UserUpdate();
				$userUpdate->setUpdatedAt($receivedTimestamp);
				$userUpdate->setUid($id);

				$this->api->beginTransaction();
				//TODO: createUser will cause the user to be sent back to UCSB, maybe add another parameter?
				$this->api->createUser($id, $receivedUser->getPassword());
				$this->userUpdateMapper->save($userUpdate);
				$this->api->commit();
			}

		}

	}

	public function updateFriendshipsWithReceivedFriendships() {
		$receivedFriendships = $this->receivedFriendshipMapper->findAll();
		
		foreach ($receivedFriendships as $receivedFriendship) {
			//TODO: try block with rollback?
			$this->api->beginTransaction();
			try {
				$friendship = $this->friendshipMapper->find($receivedFriendship->getUid1(), $receivedFriendship->getUid2());
				if ($receivedFriendship->getAddedAt() > $friendship->getUpdatedAt()) { //if newer than last update
					$this->friendshipMapper->save($receivedFriendship);
				}
			}
			catch (DoesNotExistException $e) {
				$this->friendshipMapper->save($receivedFriendship);
			}
			$this->receivedFriendshipMapper->delete($receivedFriendship->getUid1(), $receivedFriendship->getUid2(), $receivedFriendship->getAddedAt());
			$this->api->commit();
		}
	}

	public function updateUserFacebookIdsWithReceivedUserFacebookIds() {
		$receivedUserFacebookIds = $this->receivedUserFacebookIdMapper->findAll();
	
		foreach ($receivedUserFacebookIds as $receivedUserFacebookId) {
			//TODO: try block with rollback?
			$this->api->beginTransaction();
			try {
				$userFacebookId = $this->userFacebookIdMapper->find($receivedUserFacebookId->getUid());
				//TODO: check if I need to convert to datetimes?
				if ($receivedUserFacebookId->getAddedAt() > $friendship->getUpdatedAt()) {
					$this->userFacebookIdMapper->save($receivedUserFacebookId);
				}
			}
			catch (DoesNotExistException $e) {
					$this->userFacebookIdMapper->save($receivedUserFacebookId);
			}
			$this->receivedUserFacebookIdMapper->delete($receivedUserFacebookId->getUid(), $receivedUserFacebookId->getAddedAt());
			$this->api->commit();
		}
	}

/* End update methods */

/* Methods for ack content (delete queued rows) */

	public function deleteQueuedUserSql($uid, $addedAt) {
		return "DELETE IGNORE FROM \`{$this->dbtableprefix}multiinstance_queued_users\` WHERE \`uid\` = {$uid} AND \`added_at\` = {$addedAt}";
	} 

	public function deleteQueuedFriendshipSql($uid1, $uid2, $updatedAt) {
		return "DELETE IGNORE FROM \`{$this->dbtableprefix}multiinstance_queued_friendships\` WHERE \`friend_uid1\` = {$uid1}  AND \`friend_uid2\` = {$uid2} AND \`updated_at\` = {$updatedAt}";
	}
	
	public function deleteQueuedUserFacebookIdSql($uid, $syncedAt) {
		return "DELETE IGNORE FROM \`{$this->dbtableprefix}multiinstance_queued_user_facebook_ids\` WHERE \`uid\` = {$uid} AND \`friends_synced_at\` = {$syncedAt}";
	}

/* End methods for ack content */

}
