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


class FacebookFriend {

	private $uid;
	private $facebookFriendId;

	public function __construct($fromRow=null){
		if($fromRow){
			$this->fromRow($fromRow);
		}
	}

	public function fromRow($row){
		$this->uid = $row['uid'];
		$this->facebookFriendId = $row['facebook_friend_id'];
	}


	public function getUid(){
		return $this->uid;
	}

	public function getFacebookFriendId(){
		return $this->facebookFriendId;
	}

	public function setUid($uid){
		$this->uid = $uid;
	}

	public function setFacebookFriendId($facebookFriendId){
		$this->facebookFriendId = $facebookFriendId;
	}
	
	public static function createFromList($facebookResponseFriendsArray, $currentUserId){
		$result = array();
		foreach ($facebookResponseFriendsArray as $facebookObj) {
			$facebookFriend = new static();
			$facebookFriend->setUid($currentUserId);
			$facebookFriend->setFacebookFriendId($facebookObj->id);
			array_push($result, $facebookFriend);
		}
		return $result;
	}	

}
