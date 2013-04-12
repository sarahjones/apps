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

namespace OCA\MultiInstance\Core;


/* Methods for updating instance db rows based on received rows */
class UpdateReceived {
	

	private $api; 
	private $receivedUserMapper;
	private $userUpdateMapper;
	private $receivedFriendshipMapper;
	private $receivedUserFacebookIdMapper;


	/**
	 * @param API $api: an api wrapper instance
	 */
	public function __construct($api, $receivedUserMapper, $userUpdateMapper, $receivedFriendshipMapper, $receivedUserFacebookIdMapper){
		$this->api = $api;
		$this->receivedUserMapper = $receivedUserMapper;
		$this->userUpdateMapper = $userUpdateMapper;
		$this->receivedFriendshipMapper = $receivedFriendshipMapper;
		$this->receivedUserFacebookIdMapper = $receivedUserFacebookIdMapper;
	}


	public function updateUsersWithReceivedUsers() {
		$receivedUsers = $this->receivedUserMapper->findAll();		

		foreach ($receivedUsers as $receivedUser){
			$id = $receivedUser->getUid();
			$receivedTimestamp = $receivedUser->AddedAt();

			if ($this->api->userExists($id)) {
				$this->api->beginTransaction();

				//TODO: All of this should be wrapped in a try block with a rollback...
				$userUpdate = $this->userUpdateMapper->find($id);	
				//if this is new
				if ($receivedTimestamp > $userUpdate->getUpdatedAt()) {
					$userUpdate->setUpdatedAt($receivedTimestamp);	
					$this->userUpdateMapper->update($userUpdate);
					OC_User::setPassword($id, $receivedUser->getPassword());
					OC_User::setDisplayName($id, $receivedUser->getDisplayname());
					
				}
				$this->receivedUserMapper->delete($id, $receivedTimestamp);

				$this->api->commit();
			}
			else {
				$userUpdate = new UserUpdate();
				$userUpdate->setUpdatedAt($receivedTimestamp);
				$userUpdate->setUid($id);

				$this->api->beginTransaction();
				//TODO: createUser will cause the user to be sent back to UCSB, maybe add another parameter?
				$this->api->createUser($id, $receivedUser->getPassword());
				$this->userUpdateMapper->save($userUpdate);
				$this->api->commit();
			}

		}

	}

	public function updateFriendshipsWithReceivedFriendships() {
		$receivedFriendships = $this->receivedFriendshipMapper->findAll();
		
		foreach ($receivedFriendships as $receivedFriendship) {
			//TODO: try block with rollback?
			$this->api->beginTransaction();
			try {
				$friendship = $this->friendshipMapper->find($receivedFriendship->getUid1(), $receivedFriendship->getUid2());
				if ($receivedFriendship->getAddedAt() > $friendship->getUpdatedAt()) { //if newer than last update
					$this->friendshipMapper->save($receivedFriendship);
				}
			}
			catch (DoesNotExistException $e) {
				$this->friendshipMapper->save($receivedFriendship);
			}
			$this->receivedFriendshipMapper->delete($receivedFriendship->getUid1(), $receivedFriendship->getUid2(), $receivedFriendship->getAddedAt());
			$this->api->commit();
		}
	}

	public function updateUserFacebookIdsWithReceivedUserFacebookIds() {
		$receivedUserFacebookIds = $this->receivedUserFacebookIdMapper->findAll();
	
		foreach ($receivedUserFacebookIds as $receivedUserFacebookId) {
			//TODO: try block with rollback?
			$this->api->beginTransaction();
			try {
				$userFacebookId = $this->userFacebookIdMapper->find($receivedUserFacebookId->getUid());
				//TODO: check if I need to convert to datetimes?
				if ($receivedUserFacebookId->getAddedAt() > $friendship->getUpdatedAt()) {
					$this->userFacebookIdMapper->save($receivedUserFacebookId);
				}
			}
			catch (DoesNotExistException $e) {
					$this->userFacebookIdMapper->save($receivedUserFacebookId);
			}
			$this->receivedUserFacebookIdMapper->delete($receivedUserFacebookId->getUid(), $receivedUserFacebookId->getAddedAt());
			$this->api->commit();
		}
	}


}
