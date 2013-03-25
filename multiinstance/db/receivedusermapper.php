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

use \OCA\AppFramework\Core\API;
use \OCA\AppFramework\Db\Mapper;
use \OCA\AppFramework\Db\DoesNotExistException;


class ReceivedUserMapper extends Mapper {


	private $tableName;

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*multiinstance_received_users';
	}

	/**
	 * Finds all Items
	 * @return array containing all items
	 */
	public function findAll(){
		$result = $this->findAllQuery($this->tableName);

		$entityList = array();
		while($row = $result->fetchRow()){
			$entity = new ReceivedUser($row);
			array_push($entityList, $row);
		}

		return $entityList;
	}


	/**
	 * Deletes an item
	 * @param string $uid: the uid of the ReceivedUser
	 */
	public function delete($receivedUser){
		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE `uid` = ? AND `displayname` = ? AND `password` = ? AND `added_at` = ?';
		$params = array(
			$receivedUser->getUid(),
			$receivedUser->getDisplayname(),
			$receivedUser->getPassword(),
			$receivedUser->getAddedAt()
		);
		
		return $this->execute($sql, $params);
	}


}
