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
	/**
	 * @param API $api: an api wrapper instance
	 */
	public function __construct($api){
		$this->api = $api;
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
		$usersTable = $this->api->getSystemValue('dbtableprefix') . 'users';
		$file = "/home/sjones/public_html/dev/apps/multiinstance/db_sync/queued_users.sql";
		
		$cmd = "mysqldump --add-locks --replace  --skip-comments --skip-extended-insert --no-create-info --no-create-db -u" . $username .  " -p" . $password . " " . $db . " " . $table . " > " . $file;
		$escaped_comannd = escapeshellcmd($cmd); //escape since input is taken from config/conf.php
		exec($cmd);
		$replace = "sed -i 's/" . $table . "/" . $usersTable . "/g' " . $file ;
		exec(escapeshellcmd($replace));
		$eof = "sed -i '1i-- done' " . $file ;
		exec($eof);
	}


	public function insertQueuedUsers() {
		$username = $this->api->getSystemValue('dbuser');
		$password = $this->api->getSystemValue('dbpassword');
		$db = $this->api->getSystemValue('dbname');
		$table = $this->api->getSystemValue('dbtableprefix') . 'multiinstance_queued_users';
		$usersTable = $this->api->getSystemValue('dbtableprefix') . 'users';
		$path_prefix ="/home/sjones/public_html/dev/apps/multiinstance/db_sync_recv/"; 
		$file = "/queued_users.sql";

		$dirs = glob($path_prefix . "*", GLOB_ONLYDIR );

		foreach ($dirs as $dir){
			$full_file =  $dir . $file;
			$this->mysqlExecuteFile($full_file);
		}
	}

	private function execute($sql, array $params=array(), $limit=null, $offset=null){
		$query = $this->api->prepareQuery($sql);
		return $query->execute($params);
	}

	//source: http://stackoverflow.com/questions/7840044/how-to-execute-mysql-script-file-in-php
	private function mysqlExecuteFile($filename){
		$first = true;
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
				if (!empty($query) && $query !== ";") {
					if ($this->execute($query))
						//ACK
						;
		    		}
			}
	    	}
	}

}
