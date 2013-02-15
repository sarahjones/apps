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


class UserFacebookIdMapper extends Mapper {



	private $tableName;

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*friends_user_facebook_ids';
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

	public function findByFacebookId($facebookId){
		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE facebook_id = ?';
		$params = array($facebookId);
	
		$result = array();

		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException('UserFacebookId with facebookId ' . $facebookId . ' does not exist!');
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException('UserFacebookId with facebookId ' . $facebookId . ' returned more than one result.');
		}
		return new UserFacebookId($row);

	}

	public function find($uid, $facebookId=null){
		if ($facebookId){
			$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE uid = ? AND facebook_id = ?';
			$params = array($uid, $facebookId);
		}
		else {
			$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE uid = ?';
			$params = array($uid);
		}
		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException('UserFacebookId with uid ' . $uid . ' and facebookId ' . $facebookId . ' does not exist!');
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException('UserFacebookId with uid ' . $uid . ' and facebookId ' . $facebookId . ' returned more than one result.');
		}
		return new UserFacebookId($row);

	}

	/** 
	 * Checks to see if a row already exists
	 * @param $uid - the owncloud uid of a user
	 * @param $facebookId - the Facebook identifier of the same user
	 * @return boolean: whether or not it exists (note: will return true if more than one is found)
	 */
	public function exists($uid, $facebookId=null){
		try{
			$this->find($uid, $facebookId);
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
	 * @param  $userFacebookId: the UserFacebookId object to be saved
	 * @return true if successful
	 */
	public function save($userFacebookId){
		if ($this->exists($userFacebookId->getUid())){
			throw new AlreadyExistsException('Cannot save UserFacebookId with uid = ' . $userFacebookId->getUid() . ' because it already exists');
		}
		$sql = 'INSERT INTO `'. $this->tableName . '` (uid, facebook_id, facebook_name)'.
				' VALUES(?, ?, ?)';

		$params = array(
			$userFacebookId->getUid(),
			$userFacebookId->getFacebookId(),
			$userFacebookId->getFacebookName()
		);

		return $this->execute($sql, $params);
	}


	public function updateSyncTime($uid){

		$date = new \DateTime("now");
		$date = date('Y-m-d H:i', $date->format('U') - $date->getOffset());

		$sql = 'UPDATE `' . $this->tableName . '` SET friends_synced_at = ? WHERE uid = ?';
		$params = array(
			$date,
			$uid
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
