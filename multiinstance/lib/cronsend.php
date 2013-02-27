<?php
/**
 * ownCloud - Multi Instance
 *
 * @author Sarah Jones
 * @copyright 2013 Sarah Jones sarah.e.p.jones@gmail.com
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

namespace OCA\MultiInstance\Lib;

use OCA\MultiInstance\Db\QueuedUser;

/**
 * This class contains all hooks.
 */
class CronSend{


	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		$this->api = $api;
		$this->username = $this->api->getSystemValue('dbuser');
		$this->password = $this->api->getSystemValue('dbpassword');
		$this->db = $this->api->getSystemValue('dbname');
		$this->table = $this->api->getSystemValue('dbtableprefix') . 'multiinstance_queued_users';
	}

	public function dump_queued_users() {

		echo $table;

		$cmd = "mysqldump -u" . $username .  " -p" . $password . " " . $db . " " . $table . " > queued_users.sql";
		exec($cmd);
	}
}
