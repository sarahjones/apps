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


class FriendshipRequestMapper extends Mapper {



	private $tableName;

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*friends_friendship_requests';
	}




	/**
	 * Finds all users requesting friendship of the user 
	 * @param string $userId: the id of the user that we want to find friendship requests for
	 * @return an array of user uids
	 */
	public function findAllRecipientFriendshipRequestsByUser($userId){
		$sql = 'SELECT requester_uid FROM `' . $this->tableName . '` WHERE recipient_uid = ?';
		$params = array($userId);

		$result = array();
		
		$query_result = $this->execute($sql, $params);
		while($row = $query_result->fetchRow()){
			$requester = $row['requester_uid'];
			array_push($result, $requester);
		}

		return $result;
	}


	/**
	 * Finds all users the user is requesting friendships with 
	 * @param string $userId: the id of the user that we want to find friendship-request recipients for
	 * @return an array of user uids
	 */
	public function findAllRequesterFriendshipRequestsByUser($userId){
		$sql = 'SELECT recipient_uid FROM `' . $this->tableName . '` WHERE requester_uid = ?';
		$params = array($userId);

		$result = array();
		$query_result = $this->execute($sql, $params);
		while($row =  $query_result->fetchRow()){
			$recipient = $row['recipient_uid'];
			array_push($result, $recipient);
		}

		return $result;
	}



	/**
	 * Saves a friendship into the database
	 * @param  $friendship: the friendship to be saved
	 * @return true if successful
	 */
	public function save($friendship_request){
		if (!$this->api->userExists($friendship_request->getRecipient())){
			//TODO Return some sort of error message?
			return false;
		}
		if($this->exists($friendship_request->getRequester(), $friendship_request->getRecipient())){
			throw new AlreadyExistsException('Cannot save FriendshipRequest with requester_uid = ' . $friendship_request->getRequester() . ' and recipient_uid = ' . $friendship_request->getRecipient() . ' already exists.');
		}

		$sql = 'INSERT INTO `'. $this->tableName . '` (requester_uid, recipient_uid)'.
			' VALUES(?, ?)';

		$params = array(
			$friendship_request->getRequester(),
			$friendship_request->getRecipient()
		);

		return $this->execute($sql, $params);
	}

	/** 
	 * Checks to see if a row already exists
	 * @param $requester - the requester id
	 * @param $recipient - the recipient id
	 * @return boolean: whether or not it exists (note: will return true if more than one is found)
	 */
	public function exists($requester, $recipient){
		try{
			$this->find($requester, $recipient);
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
	 * Finds a friendship request
	 * @param requester: the user initiating the friend request
	 * @param recipient: the user receiving the friend request
	 */
	public function find($requester, $recipient){
		$sql = 'SELECT * FROM `'. $this->tableName . '` WHERE requester_uid = ? AND recipient_uid = ?';
		$params = array(
			$requester,
			$recipient
		);

		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException('FriendshipRequest with requester ' . $requester . ' and recipient ' . $recipient . ' does not exist!');
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException('FriendshipRequest with requester ' . $requester . ' and recipient ' . $recipient . ' returned more than one result!');
		}
		return new FriendshipRequest($row);
		
	}


	/**
	 * Deletes a friendship
	 * @param userId1: the first user
	 * @param userId2: the second user
	 */
	public function delete($userId1, $userId2){
		//must check both ways to delete friend
		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE (requester_uid = ? AND recipient_uid = ?) OR (requester_uid = ? AND recipient_uid = ?)';
		$params = array($userId1, $userId2, $userId2, $userId1);
		
		return $this->execute($sql, $params);
	
	}


}
