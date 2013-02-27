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
use OCA\MultiInstance\DependencyInjection\DIContainer;
/**
 */
class CronSend{


	/**
	 * @param API $api: Instance of the API abstraction layer
	 */

	public static function dump_queued_users() {
		$c = new DIContainer();
	
		$api = $c['API'];
		$username = $api->getSystemValue('dbuser');
		$password = $api->getSystemValue('dbpassword');
		$db = $api->getSystemValue('dbname');
		$table = $api->getSystemValue('dbtableprefix') . 'multiinstance_queued_users';
		echo $table;

		$cmd = "mysqldump -u" . $username .  " -p" . $password . " " . $db . " " . $table . " > queued_users.sql";
		exec($cmd);
	}
}
