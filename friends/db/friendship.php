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

namespace OCA\Friends;


class Friendship {

	private $uid1;
	private $uid2;

	public function __construct($fromRow=null){
		if($fromRow){
			$this->fromRow($fromRow);
		}
	}

	public function fromRow($row){
		$this->uid1 = $row['friend_uid1'];
		$this->uid2 = $row['friend_uid2'];
	}


	public function getUid1(){
		return $this->uid1;
	}

	public function getUid2(){
		return $this->uid2;
	}

	public function setUid1($uid){
		$this->uid1 = $uid;
	}

	public function setUid2($uid){
		$this->uid2 = $uid;
	}


}
