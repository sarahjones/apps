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


namespace OCA\MultiInstance\Db;

use \OCA\AppFramework\Core\API as API;
use \OCA\AppFramework\Db\Mapper as Mapper;
use \OCA\AppFramework\Db\DoesNotExistException as DoesNotExistException;
use \OCA\AppFramework\Db\MultipleObjectsReturnedException as MultipleObjectsReturnedException;
use OCA\Friends\Db\AlreadyExistsException as AlreadyExistsException;


class ReceivedRequestMapper extends Mapper {



	private $tableName;

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*multiinstance_received_requests';
	}



	/**
	 * @throws DoesNotExistException: if the item does not exist
	 * @throws MultipleObjectsReturnedException: if more than one row with those ids exists
	 * @return an object
	 */
	public function find($type, $location, $addedAt){
		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE request_type = ? AND location = ? AND added_at = ?';
		$params = array($type, $location, $addedAt);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();


		if ($row === false) {
			throw new DoesNotExistException('ReceivedRequest with request_type ' . $type . ' and location' . $location . ' and added_at ' . $addedAt . ' does not exist!');
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException('ReceivedRequest with request_type ' . $type . ' and location' . $location . ' and added_at ' . $addedAt . ' returned more than one result.');
		}
		return new ReceivedRequest($row);
	}	

	public function delete($type, $location, $addedAt) {
		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE (request_type = ? AND location = ? AND added_at = ?)';
		$params = array($type, $location, $syncedAt);

		return $this->execute($sql, $params);
	}

}
