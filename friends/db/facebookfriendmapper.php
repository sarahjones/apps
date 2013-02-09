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


class FacebookFriendMapper extends Mapper {



	private $tableName;

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*friends_facebook_friends';
	}



	/**
	 * Finds all Facebook friends' owncloud uid in owncloud (should be used for the current user)
	 * @param Facebook id string $currentUserFacebookId: the Facebook identifier of the user to find friends for
	 */
	public function findAllFacebookFriendsUids($currentUserFacebookId){
		$sql = 'SELECT uid FROM `'. $this->tableName . '` WHERE facebook_friend_id = ?';
		
		$params = array($currentUserFacebookId);

		$result = array();
		$query_result = $this->execute($sql, $params);
		while ($row = $query_result->fetchRow()){
			array_push($result, $row['uid']);
		}	
		return $result;
	}

	public function find($uid, $facebookFriendId){
		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE uid = ? AND facebook_friend_id = ?';
		$params = array($uid, $facebookFriendId);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();
		if ($result){
		}
		else {
		}

		if ($row === false) {
			throw new DoesNotExistException('FacebookFriend with uid ' . $uid . ' and facebookFriendId ' . $facebookFriendId . ' does not exist!');
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException('FacebookFriend with uid ' . $uid . ' and facebookId ' . $facebookFriendId . ' returned more than one result.');
		}
		return new FacebookFriend($row);
	}

	/** 
	 * Checks to see if a row already exists
	 * @return boolean: whether or not it exists (note: will return true if more than one is found)
	 */
	public function exists($uid, $facebookFriendId){
		try{
			$this->find($uid, $facebookFriendId);
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
	public function save($facebookFriend){
		if ($this->exists($facebookFriend->getUid(), $facebookFriend->getFacebookFriendId())){
			throw new AlreadyExistsException('Cannot save FacebookFriend with uid = ' . $facebookFriend->getUid() . ' and facebook_friend_id = ' . $facebookFriend->getFacebookFriendId() . ' because it already exists');
		}
		$sql = 'INSERT INTO `'. $this->tableName . '` (uid, facebook_friend_id)'.
				' VALUES(?, ?)';

		$params = array(
			$facebookFriend->getUid(),
			$facebookFriend->getFacebookFriendId()
		);

		return $this->execute($sql, $params);
	}

	public function saveAll($facebookFriendsArray){
		foreach ($facebookFriendsArray as $facebookFriend){
			try{
				$this->save($facebookFriend);
			}
			catch (AlreadyExistsException $e){
				//Do nothing, just means they have sync'd before
			}
		}
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

	public function deleteBoth($uid1, $facebookFriendId1, $uid2,  $facebookFriendId2){
		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE (uid = ? AND facebook_friend_id = ?) OR (uid = ? AND facebook_friend_id = ?)';
		$params = array($uid1, $facebookFriendId2, $uid2, $facebookFriendId1);

		return $this->execute($sql, $params);
	}

}
