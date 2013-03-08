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

use \OCP\OC_User;


class CronTask {
	

	private $api; 
	private $receivedUserMapper;
	private $userUpdateMapper;
	private $locationMapper;
	
	/**
	 * @param API $api: an api wrapper instance
	 */
	public function __construct($api, $receivedUserMapper, $userUpdateMapper, $locationMapper){
		$this->api = $api;
		$this->receivedUserMapper = $receivedUserMapper;
		$this->userUpdateMapper = $userUpdateMapper;
		$this->locationMapper = $locationMapper;
	}

	/**
	 *
	 * runs cron
	 */
	public function dumpQueuedUsers() {
		$username = $this->api->getSystemValue('dbuser');
		$password = $this->api->getSystemValue('dbpassword');
		$db = $this->api->getSystemValue('dbname');
		$table = $this->api->getSystemValue('dbtableprefix') . 'multiinstance_queued_users';
		$usersTable = $this->api->getSystemValue('dbtableprefix') . 'multiinstance_received_users';
		$file = "/home/sjones/public_html/dev/apps/multiinstance/db_sync/queued_users.sql";
		
		$cmd = "mysqldump --add-locks --insert  --skip-comments --skip-extended-insert --no-create-info --no-create-db -u" . $username .  " -p" . $password . " " . $db . " " . $table . " > " . $file;
		$escaped_comannd = escapeshellcmd($cmd); //escape since input is taken from config/conf.php
		exec($cmd);
		$replace = "sed -i 's/" . $table . "/" . $usersTable . "/g' " . $file ;
		exec(escapeshellcmd($replace));
		$eof = "sed -i '1i-- done;' " . $file ;
		exec($eof);
	}


	public function insertReceivedUsers() {
		$path_prefix ="/home/sjones/public_html/dev/apps/multiinstance/db_sync_recv/"; 
		$file = "/queued_users.sql";

		$dirs = glob($path_prefix . "*", GLOB_ONLYDIR );

		foreach ($dirs as $dir){
			$full_file =  $dir . $file;
			$ip = $this->locationMapper->findByLocation($dir);
			$this->mysqlExecuteFile($full_file, $ip);
		}
	}


	public function updateUsersWithReceivedUsers() {
		$receivedUsers = $this->receivedUserMapper->findAll();		

		foreach ($receivedUsers as $receivedUser){
			$id = $receiverUser->getUid();
			$receivedTimestamp = $receiverUser->getUpdatedAt();

			if (OC_User::userExists($id)) {
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

	private function execute($sql, array $params=array(), $limit=null, $offset=null){
		$query = $this->api->prepareQuery($sql);
		return $query->execute($params);
	}

	//source: http://stackoverflow.com/questions/7840044/how-to-execute-mysql-script-file-in-php
	protected function mysqlExecuteFile($filename, $ip){
		$first = true;
		$ackedList = array();
		if ($file = file_get_contents($filename)){
			foreach(explode(";", $file) as $query){
				$query = trim($query);
				if ($first) {
					//If still being written
					if ($query !== "-- done")
						return;
					$first = false;
					continue;
				}
				$ackedList .= $this->toAckFormat($query);
				if (!empty($query) && $query !== ";") {
					$this->execute($query);
		    		}
			}
	    	}
		$this->ack($ackedList);
	}

	/**
	 * Should be private.  Public for testing access
	 * @param $query string
	 */
	public function toAckFormat($query) {
		//INSERT  IGNORE INTO `oc_multiinstance_received_users` VALUES ('Matt@UCSB','kitty','matt',NULL);
		$matches = array();
		//$pattern = '/^INSERT.*VALUES \(.(?<uid>),[^,]*,[^,]*(?<timestamp>)\);$/';
		$pattern = '/^INSERT.*VALUES \((?<uid>[^,]+),[^,]*,[^,]*,(?<timestamp>[^,]+)\);$/';
		preg_match($pattern, $query, $matches);
		if (sizeof($matches) >= 3){
			$formattedQuery = "{$matches[1]}|{$matches[2]}";
		}
		else {
			$formattedQuery = "";
		}
		return $formattedQuery;
	}

	/**
	 * @param $ackedList string
	 */
	protected function ack($ackedList){
		$output = $this->getAppValue($this->getAppName(), 'cronErrorLog');
		exec('( ssh -f -L 23333:192.168.56.102:3334 sarah@192.168.56.102 sleep 1;  \
			echo "' . $ackedList . '" |  nc -w 1 192.168.56.102 23333 ) > ' . $output . ' 2>&1 &');	
	}

}
