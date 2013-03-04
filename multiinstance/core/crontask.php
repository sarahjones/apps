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
		$file = "/home/sjones/public_html/dev/apps/multiinstance/db_sync/queued_users.sql";
		//--no-create-info --no-create-db
		$cmd = "mysqldump --add-locks --replace -u" . $username .  " -p" . $password . " " . $db . " " . $table . " >> " . $file;
		$escaped_comannd = escapeshellcmd($cmd); //escape since input is taken from config/conf.php
		exec($cmd);
	}

}
