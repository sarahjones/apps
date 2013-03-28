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


class QueuedUserFacebookIdMapper extends Mapper {



	private $tableName;

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*multiinstance_queued_user_facebook_ids';
	}

	public function find($uid, $syncedAt){
		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `uid` = ? AND `friends_synced_at` = ?';
		$params = array($uid, $syncedAt);
		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException('UserFacebookId with uid ' . $uid . ' and syncedAt ' . $syncedAt . ' does not exist!');
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException('UserFacebookId with uid ' . $uid . ' and syncedAt ' . $syncedAt . ' returned more than one result.');
		}
		return new QueuedUserFacebookId($row);

	}

	/** 
	 * Checks to see if a row already exists
	 * @param $uid - the owncloud uid of a user
	 * @param $facebookId - the Facebook identifier of the same user
	 * @return boolean: whether or not it exists (note: will return true if more than one is found)
	 */
	public function exists($uid, $syncedAt){
		try{
			$this->find($uid, $syncedAt);
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
	 * 
	 * @param  $userFacebookId: the UserFacebookId object to be saved
	 * @return true if successful
	 */
	public function save($userFacebookId){
		if ($this->exists($userFacebookId->getUid(), $userFacebookId->getFriendsSyncedAt())){
			throw new AlreadyExistsException('Cannot save UserFacebookId with uid = ' . $userFacebookId->getUid() . ' because it already exists');
		}
		$sql = 'INSERT INTO `'. $this->tableName . '` (`uid`, `facebook_id`, `facebook_name`, `friends_synced_at`)'.
				' VALUES(?, ?, ?, ?)';

		$params = array(
			$userFacebookId->getUid(),
			$userFacebookId->getFacebookId(),
			$userFacebookId->getFacebookName(),
			$userFacebookId->getFriendsSyncedAt()
		);

		return $this->execute($sql, $params);
	}

	/**
	 * 
	 * @param 
	 * @param 
	 */
	public function delete($userId, $syncedAt){
		
		//must check both ways to delete friend
		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE (uid = ? AND friends_synced_at = ?)';
		$params = array($uid, $syncedAt);
		
		return $this->execute($sql, $params);
	
	}


}
