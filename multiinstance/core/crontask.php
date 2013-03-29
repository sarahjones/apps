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
	public function __construct($api, $receivedUserMapper, $userUpdateMapper, $locationMapper){
		$this->api = $api;
		$this->receivedUserMapper = $receivedUserMapper;
		$this->userUpdateMapper = $userUpdateMapper;
		$this->locationMapper = $locationMapper;

		$this->dbuser = $this->api->getSystemValue('dbuser'); 
		$this->dbpassword = $this->api->getSystemValue('dbpassword'); 
		$this->dbname = $this->api->getSystemValue('dbname'); 
		$this->dbtableprefix = $this->api->getSystemValue('dbtableprefix');
		$this->recvPathPrefix = $this->api->getAppValue('dbSyncRecvPath'); 
		$this->sendPathPrefix = $this->api->getAppValue('dbSyncPath');


	}

	/**
	 *
	 * runs cron
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


	public function insertReceived() {
		$dirs = glob($this->recvPathPrefix . "*", GLOB_ONLYDIR );

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


	public function updateUsersWithReceivedUsers() {
		$receivedUsers = $this->receivedUserMapper->findAll();		

		foreach ($receivedUsers as $receivedUser){
			$id = $receiverUser->getUid();
			$receivedTimestamp = $receiverUser->getUpdatedAt();

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
				$this->receivedUserMapper->delete($id);

				$this->api->commit();
			}
			else {
			//  createUser -- need to modify create user to handle if the username already has @
				//OC_User::createUser($id, $receiverUser->getPassword());
			}

		}

	}

	//Copied from OCA\AppFramework\Db\Mapper for general query execution
	private function execute($sql, array $params=array(), $limit=null, $offset=null){
		$query = $this->api->prepareQuery($sql); //PDO object
		return $query->execute($params);
	}

	//source: http://stackoverflow.com/questions/7840044/how-to-execute-mysql-script-file-in-php
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
	 * Should be private.  Public for testing access
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
	 * @param $ackedList string
	 * @param $ip string - IP of the village to send the ack back to
	 */
	private function ack($ackedList, $locationName){
		$time = $this->api->microTime();
		$filename = "{$this->sendPathPrefix}{$locationName}/{$time}";
		$cmd = "echo \"{$ackedList}\" >> {$filename}";
		$this->api->exec($cmd);
	}

	public function deleteQueuedUserSql($uid, $addedAt) {
		return "DELETE IGNORE FROM \`{$this->dbtableprefix}multiinstance_queued_users\` WHERE \`uid\` = {$uid} AND \`added_at\` = {$addedAt}";
	} 

	public function deleteQueuedFriendshipSql($uid1, $uid2, $updatedAt) {
		return "DELETE IGNORE FROM \`{$this->dbtableprefix}multiinstance_queued_friendships\` WHERE \`friend_uid1\` = {$uid1}  AND \`friend_uid2\` = {$uid2} AND \`updated_at\` = {$updatedAt}";
	}
	
	public function deleteQueuedUserFacebookIdSql($uid, $syncedAt) {
		return "DELETE IGNORE FROM \`{$this->dbtableprefix}multiinstance_queued_user_facebook_ids\` WHERE \`uid\` = {$uid} AND \`friends_synced_at\` = {$syncedAt}";
	}
}
