<?php
/**
* ownCloud - MultiInstance App
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


namespace OCA\MultiInstance\Db;

use \OCA\AppFramework\Core\API as API;
use \OCA\AppFramework\Db\Mapper as Mapper;
use \OCA\AppFramework\Db\DoesNotExistException as DoesNotExistException;
use \OCA\AppFramework\Db\MultipleObjectsReturnedException as MultipleObjectsReturnedException;
use OCA\Friends\Db\AlreadyExistsException as AlreadyExistsException;
use OCA\MultiInstance\Db\QueuedRequest;

class QueuedRequestMapper extends Mapper {



	private $tableName;

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*multiinstance_queued_requests';
	}



	/**
	 */
	public function save($request) {
		//Do not need to check if it already exists, because it will be using the unique key of the location and id (which is autoincrementing)
		$sql = 'INSERT INTO `'. $this->tableName . '` (request_type, location, added_at, field1)'.
			' VALUES(?, ?, ?, ?)';
		$params = array($request->getType(), $request->getLocation(), $request->getAddedAt(), $request->getField1());
		return $this->execute($sql, $params);
	}

}
