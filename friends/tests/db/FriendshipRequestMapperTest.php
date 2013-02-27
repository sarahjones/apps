<?php

/**
 * ownCloud - App Framework
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

require_once(__DIR__ . "/../classloader.php");

use \OCA\AppFramework\Db\DoesNotExistException as DoesNotExistException;

class FriendshipRequestMapperTest extends \PHPUnit_Framework_TestCase {

	private $api;

	protected function setUp(){
		$this->api = $this->getMock('OCA\Friends\Core\FriendsApi', array('prepareQuery', 'userExists'), array('friends'));
		$this->mapper = new FriendshipRequestMapper($this->api);
		$this->row1 = array(
			//'requester_uid' => 'thisisuser3',
			//'recipient_uid' => 'thisisuser1'
			'requester_uid' => 'thisisuser3'
		);
		$this->row2 = array(
			//'requester_uid' => 'thisisuser1',
			//'recipient_uid' => 'thisisuser2'
			'recipient_uid' => 'thisisuser2'
		);
		$this->row3 = array(
			'recipient_uid' => 'thisisuser1',
			'requester_uid' => 'thisisuser2'
		);
	}



 	public function testFindAllRecipientFriendshipRequestsByUser(){
		$userId = 'thisisuser1';
		$expected = 'SELECT requester_uid FROM `*PREFIX*friends_friendship_requests` WHERE recipient_uid = ?';

		$cursor = $this->getMock('cursor', array('fetchRow'));
		$cursor->expects($this->at(0))
			->method('fetchRow')
			->will($this->returnValue($this->row1));
		$cursor->expects($this->at(1))
			->method('fetchRow')
			->will($this->returnValue(false));

		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo(array($userId)))
			->will($this->returnValue($cursor));

		$this->api->expects($this->once())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));

		$friendships = $this->mapper->findAllRecipientFriendshipRequestsByUser($userId);

		$this->assertEquals(array('thisisuser3'), $friendships);
 	}

 	public function testFindAllRequesterFriendshipRequestsByUser(){
		$userId = 'thisisuser1';
		$expected = 'SELECT recipient_uid FROM `*PREFIX*friends_friendship_requests` WHERE requester_uid = ?';

		$cursor = $this->getMock('cursor', array('fetchRow'));
		$cursor->expects($this->at(0))
			->method('fetchRow')
			->will($this->returnValue($this->row2));
		$cursor->expects($this->at(1))
			->method('fetchRow')
			->will($this->returnValue(false));

		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo(array($userId)))
			->will($this->returnValue($cursor));

		$this->api->expects($this->once())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));

		$friendships = $this->mapper->findAllRequesterFriendshipRequestsByUser($userId);

		$this->assertEquals(array('thisisuser2'), $friendships);
 	}


	public function testSave(){
		$userId1 = 'thisisuser1';
		$userId2 = 'thisisuser2';
		$params = array($userId2, $userId1);
		$expected = 'INSERT INTO `*PREFIX*friends_friendship_requests` (requester_uid, recipient_uid) VALUES(?, ?)';


		$frmapper = $this->getMock('OCA\Friends\Db\FriendshipRequestMapper', array('exists'), array($this->api));
		$frmapper->expects($this->once())
			->method('exists')
			->with($userId2, $userId1)
			->will($this->returnValue(false));
		
		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo($params))
			->will($this->returnValue(true));

		$this->api->expects($this->once())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));

		$this->api->expects($this->once())
			->method('userExists')
			->with()
			->will($this->returnValue(true));
	
		$friendshipRequest = new FriendshipRequest();
		$friendshipRequest->setRecipient($userId1);
		$friendshipRequest->setRequester($userId2);

		$result = $frmapper->save($friendshipRequest);
		$this->assertEquals(true, $result);
		
	}
	public function testSaveShouldFailIfFriendRequestAlreadyExists(){
		$userId1 = 'thisisuser1';
		$userId2 = 'thisisuser2';

		$this->api->expects($this->once())
			->method('userExists')
			->with()
			->will($this->returnValue(true));

		$frmapper = $this->getMock('OCA\Friends\Db\FriendshipRequestMapper', array('exists'), array($this->api));
		$frmapper->expects($this->once())
			->method('exists')
			->with($userId2, $userId1)
			->will($this->returnValue(true));

	
		$friendshipRequest = new FriendshipRequest();
		$friendshipRequest->setRecipient($userId1);
		$friendshipRequest->setRequester($userId2);

		$this->setExpectedException('OCA\Friends\Db\AlreadyExistsException');
		$result = $frmapper->save($friendshipRequest);
		
	}

	/* FriendRequest exists */
	public function testFind(){
		$userId1 = 'thisisuser1';
		$userId2 = 'thisisuser2'; //requester
		$expected = 'SELECT * FROM `*PREFIX*friends_friendship_requests` WHERE requester_uid = ? AND recipient_uid = ?';

		$cursor = $this->getMock('cursor', array('fetchRow'));
		$cursor->expects($this->at(0))
			->method('fetchRow')
			->will($this->returnValue($this->row3));
		$cursor->expects($this->at(1))
			->method('fetchRow')
			->will($this->returnValue(false));

		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo(array($userId2, $userId1)))
			->will($this->returnValue($cursor));

		$this->api->expects($this->once())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));

		$result = $this->mapper->find($userId2, $userId1);
		$this->assertEquals($userId2, $result->getRequester());
		$this->assertEquals($userId1, $result->getRecipient());
		

	}

	/* FriendRequest does not exist */
	public function testFindNotExist(){
		$userId1 = 'thisisuser1';
		$userId2 = 'thisisuser2'; //requester
		$expected = 'SELECT * FROM `*PREFIX*friends_friendship_requests` WHERE requester_uid = ? AND recipient_uid = ?';

		$cursor = $this->getMock('cursor', array('fetchRow'));
		$cursor->expects($this->once())
			->method('fetchRow')
			->will($this->returnValue(false));

		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo(array($userId2, $userId1)))
			->will($this->returnValue($cursor));

		$this->api->expects($this->once())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));

		$this->setExpectedException('OCA\AppFramework\Db\DoesNotExistException');

		$this->mapper->find($userId2, $userId1);
	}

	public function testExists(){
		$userId1 = 'thisisuser1';
		$userId2 = 'thisisuser2';
		$params = array($userId2, $userId1);

		$frmapper = $this->getMock('OCA\Friends\Db\FriendshipRequestMapper', array('find'), array($this->api));
		$frmapper->expects($this->once())
			->method('find')
			->with($userId1, $userId2)
			->will($this->returnValue(true));
		

		$result = $frmapper->exists($userId1, $userId2);
		$this->assertEquals(true, $result);
	}

	public function testDelete(){
		
		$userId1 = 'thisisuser1';
		$userId2 = 'thisisuser2';
		$params = array($userId1, $userId2, $userId2, $userId1);
		$expected = 'DELETE FROM `*PREFIX*friends_friendship_requests` WHERE (requester_uid = ? AND recipient_uid = ?) OR (requester_uid = ? AND recipient_uid = ?)';
		
		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo($params))
			->will($this->returnValue(true));

		$this->api->expects($this->any())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));
	
		$result = $this->mapper->delete($userId1, $userId2);

		$this->assertEquals(true, $result);

	}

}
