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


class UserUpdateMapper extends Mapper {


	private $tableName;

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*multiinstance_user_updates';
	}

	/**
	 * Finds an item by id
	 * @throws DoesNotExistException: if the item does not exist
	 * @return the item
	 */
	public function find($uid){
		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE uid = ?';
		$params = array($uid);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException('UserUpdate with uid ' . $uid . ' does not exist!');
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException('UserUpdate with uid ' . $uid . ' returned more than one result.');
		}
		return new UserUpdate($row);

	}

	public function exists($uid){
		try{
			$this->find($uid);
		}
		catch (DoesNotExistException $e){
			return false;
		}
		catch (MultipleObjectsReturnedException $e){
			return true;
		}
		return true;
	}

	/**
	 * Finds all Items
	 * @return array containing all items
	 */
	public function findAll(){
		$result = $this->findAllQuery($this->tableName);

		$entityList = array();
		while($row = $result->fetchRow()){
			$entity = new UserUpdate($row);
			array_push($entityList, $entity);
		}

		return $entityList;
	}


	/**
	 * Saves an item into the database
	 * @param Item $userUpdate: the item to be saved
	 * @return the item with the filled in id
	 */
	public function save($userUpdate){

		$sql = 'INSERT INTO `'. $this->tableName . '` (`uid`, `updated_at`)'.
				' VALUES(?, ?)';

		$params = array(
			$userUpdate->getUid(),
			$userUpdate->getUpdatedAt()
		);

		return $this->execute($sql, $params);

	}
       public function update($queuedUser){
               $sql = 'UPDATE `'. $this->tableName . '` SET
                               `updated_at` = ?
                               WHERE `uid` = ?';

               $params = array(
                       $queuedUser->getUpdatedAt(),
                       $queuedUser->getUid()
               );

               $this->execute($sql, $params);
       }

	/**
	 * Deletes an item
	 * @param string $uid: the uid of the UserUpdate
	 */
	public function delete($userUpdate){
		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE `uid` = ?';
		$params = array(
			$userUpdate->getUid(),
		);
		
		return $this->execute($sql, $params);
	}


}
