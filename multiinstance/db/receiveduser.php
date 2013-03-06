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


class ReceivedUser {

	private $uid;
	private $displayname;
	private $password;
	private $addedAt;

	public function __construct($uid, $displayname, $password, $addedAt){
		$this->uid = $uid;
		$this->displayname = $displayname;
		$this->password = $password;
		$this->addedAt = $addedAt;

	}

	public function fromRow($row){
		$this->uid = $row['uid'];
		$this->displayname = $row['displayname'];
		$this->password = $row['password'];
		$this->addedAt = $row['added_at'];
	}


	public function getUid(){
		return $this->uid;
	}

	public function getDisplayname(){
		return $this->displayname;
	}

	public function getPassword(){
		return $this->password;
	}

	public function getAddedAt(){
		return $this->addedAt;
	}
}
