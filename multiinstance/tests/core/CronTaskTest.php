<?php

/**
* ownCloud - App Template plugin
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

namespace OCA\MultiInstance\Lib;

require_once(__DIR__ . "/../classloader.php");

use OCA\MultiInstance\Core\CronTask;

class CronTaskTest extends \PHPUnit_Framework_TestCase {

    private $api;
    private $locationMapper;
    private $userUpdateMapper;
    private $receivedUserMapper;
    private $row;

    protected function setUp(){
        $this->api = $this->getMock('OCA\MultiInstance\Core\MultiInstanceAPI', array('prepareQuery', 'getSystemValue', 'getAppValue'), array('multiinstance'));
        $this->locationMapper = $this->getMock('OCA\Multiinstance\Db\LocationMapper', array(), array($this->api));
	$this->userUpdateMapper = $this->getMock('OCA\Multiinstance\Db\UserUpdateMapper', array(), array($this->api));	
	$this->receivedUserMapper = $this->getMock('OCA\Multiinstance\Db\ReceivedUserMapper', array(), array($this->api));	
 

        $this->row = array(
            'id' => 1,
            'user' => 'john',
            'path' => '/test',
            'name' => 'right'
        );

	$this->api->expects($this->at(0))
		->method('getSystemValue')
		->with('dbuser')
		->will($this->returnValue('owncloud'));
	$this->api->expects($this->at(1))
		->method('getSystemValue')
		->with('dbpassword')
		->will($this->returnValue('password'));
	$this->api->expects($this->at(2))
		->method('getSystemValue')
		->with('dbname')
		->will($this->returnValue('dev-owncloud'));
	$this->api->expects($this->at(3))
		->method('getSystemValue')
		->with('dbtableprefix')
		->will($this->returnValue('oc_'));
	$this->api->expects($this->at(4))
		->method('getAppValue')
		->with('dbSyncRecvPath')
		->will($this->returnValue(""));


	$this->cronTask = new CronTask($this->api, null, null, null);
}
    public function testToAckFormat(){
	
	$insert = "INSERT  IGNORE INTO `oc_multiinstance_received_users` VALUES ('Matt@UCSB','kitty','matt',NULL)";
	$insertResult = $this->cronTask->toAckFormat($insert, 'multiinstance_queued_users.sql');
	$this->assertEquals("DELETE IGNORE FROM `oc_multiinstance_queued_users` WHERE `uid` = 'Matt@UCSB' AND `added_at` = NULL", $insertResult);

	$insertDate = "INSERT  IGNORE INTO `oc_multiinstance_received_users` VALUES ('Maria@UCSB','','Maria','2013-03-07 23:07:00')";
	$insertDateResult = $this->cronTask->toAckFormat($insertDate, 'multiinstance_queued_users.sql');
	$this->assertEquals("DELETE IGNORE FROM `oc_multiinstance_queued_users` WHERE `uid` = 'Maria@UCSB' AND `added_at` = '2013-03-07 23:07:00'", $insertDateResult);

	$comment = "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */";
	$commentResult = $this->cronTask->toAckFormat($comment, 'multiinstance_queued_users.sql');
	$this->assertEquals("", $commentResult);

	$sqlNotInsert = "LOCK TABLES `oc_multiinstance_received_users` WRITE";
	$sqlNotInsertResult =$this->cronTask->toAckFormat($sqlNotInsert, 'multiinstance_queued_users.sql');
	$this->assertEquals("", $sqlNotInsertResult);
    }

}
