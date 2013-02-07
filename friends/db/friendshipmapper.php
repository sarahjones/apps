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


namespace OCA\Friends\Db;

use \OCA\AppFramework\Core\API as API;
use \OCA\AppFramework\Db\Mapper as Mapper;
use \OCA\AppFramework\Db\DoesNotExistException as DoesNotExistException;
use \OCA\AppFramework\Db\MultipleObjectsReturnedException as MultipleObjectsReturnedException;
use OCA\Friends\Db\AlreadyExistsException as AlreadyExistsException;


class FriendshipMapper extends Mapper {



	private $tableName;

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*friends_friendships';
	}




	/**
	 * Finds all friends for a user 
	 * @param string $userId: the id of the user that we want to find friends for
	 * @throws DoesNotExistException: if the item does not exist
	 * @return an array of friends
	 */
	public function findAllFriendsByUser($userId){
		$sql = 'SELECT friend_uid2 as friend FROM `' . $this->tableName . '` WHERE friend_uid1 = ?
			UNION
			SELECT friend_uid1 as friend FROM `' . $this->tableName . '` WHERE friend_uid2 = ?';
		$params = array($userId, $userId);

		$result = array();
		
		$query_result = $this->execute($sql, $params);
		while($row =  $query_result->fetchRow()){
			$friend = $row['friend'];
			array_push($result, $friend);
		}
		return $result;
	}

	/**
	 * Finds a friendship  
	 * @param string $userId1: the id of one of the users
	 * @param string $userId2: the id  of the other user
	 * @throws DoesNotExistException: if the item does not exist
	 * @throws MultipleObjectsReturnedException: if more than one friendship with those ids exists
	 * @return a friendship object
	 */
	public function find($userId1, $userId2){
		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE friend_uid1 = ? AND friend_uid2 = ?
			UNION
			SELECT * FROM `' . $this->tableName . '` WHERE friend_uid1 = ? AND friend_uid2 = ?';
		$params = array($userId1, $userId2, $userId2, $userId1);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === null) {
			throw new DoesNotExistException('Friendship with users ' . $userId1 . ' and ' . $userId2 . ' does not exist!');
		} elseif($result->fetchRow() !== null) {
			throw new MultipleObjectsReturnedException('Friendship with users ' .$userId1 . ' and ' . $userId2 . ' returned more than one result.');
		}
		return new Friendship($row);
	}


	/** 
	 * Checks to see if a row already exists
	 * @param $userId1 - the first user id
	 * @param $userId2 - the second user id
	 * @return boolean: whether or not it exists (note: will return true if more than one is found)
	 */
	public function exists($userId1, $userId2){
		try{
			$this->find($userId1, $userId2);
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
	 * Saves a friendship into the database
	 * @param  $friendship: the friendship to be saved
	 * @return true if successful
	 */
	public function save($friendship){
		if ($this->exists($friendship->getUid1(), $friendship->getUid2())){
			throw new AlreadyExistsException('Cannot save Friendship with friend_uid1 = ' . $friendship->getUid1() . ' and friend_uid2 = ' . $friendship->getUid2());
		}
		$sql = 'INSERT INTO `'. $this->tableName . '` (friend_uid1, friend_uid2)'.
				' VALUES(?, ?)';

		$params = array(
			$friendship->getUid1(),
			$friendship->getUid2()
		);

		return $this->execute($sql, $params);
	}


	/**
	 * Deletes a friendship
	 * @param userId1: the first user
	 * @param userId2: the second user
	 */
	public function delete($userId1, $userId2){
		
		//must check both ways to delete friend
		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE (friend_uid1 = ? AND friend_uid2 = ?) OR (friend_uid1 = ? AND friend_uid2 = ?)';
		$params = array($userId1, $userId2, $userId2, $userId1);
		
		return $this->execute($sql, $params);
	}


}
