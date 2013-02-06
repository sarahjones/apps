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

	public function find($userId1, $userId2){
		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE friend_uid1 = ? AND friend_uid2 = ?
			UNION
			SELECT * FROM `' . $this->tableName . '` WHERE friend_uid1 = ? AND friend_uid2 = ?';
		$params = array($userId1, $userId2, $userId2, $userId1);

		$result = array();
		
		$result = $this->execute($sql, $params)->fetchRow();
		if ($result){
			return new Friendship($result);
		}
		else {
			throw new DoesNotExistException('Friendship with users ' . $userId1 . ' and ' . $userId2 . ' does not exist!');
		}

	}


	/**
	 * Saves a friendship into the database
	 * @param  $friendship: the friendship to be saved
	 * @return true if successful
	 */
	public function save($facebookFriend){
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
			$this->save($facebookFriend);
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


}
