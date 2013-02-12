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

namespace OCA\Friends\Controller;

use OCA\AppFramework\Http\Request;
use OCA\AppFramework\Db\DoesNotExistException;
use OCA\AppFramework\Utility\ControllerTestUtility;

use OCA\Friends\Db\Friendship;
use OCA\Friends\Db\UserFacebookId;
use OCA\Friends\Core\FileGetContentsWrapper;

require_once(__DIR__ . "/../classloader.php");


class FriendshipControllerTest extends ControllerTestUtility {


	public function testRedirectToIndexAnnotations(){
		$api = $this->getAPIMock();
		$controller = new FriendshipController($api, new Request(), null);
		$methodName = 'redirectToIndex';
		$annotations = array('CSRFExemption', 'IsAdminExemption', 'IsSubAdminExemption');

		$this->assertAnnotations($controller, $methodName, $annotations);
	}


	public function testIndexAnnotations(){
		$api = $this->getAPIMock();
		$controller = new FriendshipController($api, new Request(), null);
		$methodName = 'index';
		$annotations = array('CSRFExemption', 'IsAdminExemption', 'IsSubAdminExemption');

		$this->assertAnnotations($controller, $methodName, $annotations);
	}


	public function testIndexGetSystemValue(){
		$api = $this->getAPIMock();
/*		$api->expects($this->any())
					->method('getSystemValue')
					->with($this->equalTo('somesetting'))
					->will($this->returnValue('systemvalue'));
*/
		$friendshipMapperMock = $this->getMock('FriendshipMapper');
		$friendshipRequestMapperMock = $this->getMock('FriendshipRequestMapper');
		$userFacebookIdMapperMock = $this->getMock('UserFacebookIdMapper');
		$facebookFriendMapperMock = $this->getMock('FacebookFriendMapper');

		$controller = new FriendshipController($api, new Request(), null, null, null, null);

		$response = $controller->index();
		$this->assertEquals('main', $response->getTemplateName());
	}


/*	public function testIndexFriendshipExists(){
		$api = $this->getAPIMock();
		$api->expects($this->any())
					->method('getUserId')
					->will($this->returnValue('richard'));

		$item = new Friendship();
		$item->setUser('user');
		$item->setPath('/path');
		$item->setId(3);
		$item->setName('name');

		$itemMapperMock = $this->getMock('FriendshipMapper', array('findByUserId'));
		$itemMapperMock->expects($this->any())
					->method('findByUserId')
					->will($this->returnValue($item));
		
		$controller = new FriendshipController($api, new Request(), $itemMapperMock);

		$response = $controller->index();
		$params = $response->getParams();
		$this->assertEquals($item, $params['item']);
	}
*/

	public function testIndexFriendshipDoesNotExist(){
		$api = $this->getAPIMock();
		$api->expects($this->any())
					->method('getUserId')
					->will($this->returnValue('richard'));

		$itemMapperMock = $this->getMock('FriendshipMapper', array('findByUserId', 'save'));
		$itemMapperMock->expects($this->any())
					->method('findByUserId')
					->will($this->throwException(new DoesNotExistException('')));

		$controller = new FriendshipController($api, new Request(), $itemMapperMock);

		$response = $controller->index();
		$params = $response->getParams();

		$this->assertEquals('richard', $params['item']->getUser());
		$this->assertEquals('/home/path', $params['item']->getPath());
		$this->assertEquals('john', $params['item']->getName());
	}

	public function testFacebookSyncFirstLoad(){
		$api = $this->getAPIMock();
		$controller = new FriendshipController($api, new Request(), null, null, null, null, null);

		$response = $controller->facebookSync();
		$params = $response->getParams();
		$this->assertRegExp('/.*facebook\.com\/dialog\//', $params['fb_dialog_url']);
	}

	//Redirected back from Facebook after permissions request
	public function testFacebookSyncResponseRedirect(){
		$api = $this->getAPIMock('OCA\Friends\Core\FriendsAPI');
		$friendshipMapperMock = $this->getMock('FriendshipMapper', array('exists'));
		$friendshipRequestMapperMock = $this->getMock('FriendshipRequestMapper');
		$userFacebookIdMapperMock = $this->getMock('UserFacebookIdMapper', array('exists', 'find'));
		$facebookFriendMapperMock = $this->getMock('FacebookFriendMapper', array('saveAll', 'findAllFacebookFriendsUids', 'deleteBoth'));
		$fileGetContentsWrapperMock = $this->getMock('FileGetContentsWrapper', array('fetch'));

		$controller = new FriendshipController($api, new Request(), $friendshipMapperMock, $friendshipRequestMapperMock, $userFacebookIdMapperMock, $facebookFriendMapperMock, $fileGetContentsWrapperMock);
		$controller->my_url = "http://myfakeurl.com/index.php";
		$controller->app_id = "myid";
		$controller->app_secret = "mysecret";
		
		$api->expects($this->at(0))
					->method('getUserId')
					->will($this->returnValue('Sarah')); //current user
		

		$tokenUrl = "https://graph.facebook.com/oauth/access_token?"
                                        . "client_id=myid&redirect_uri=" . urlencode($controller->my_url)
                                        . "&client_secret=mysecret&code=mycode";
		$fetchedAccessToken = 'access_token=AAAFji9Iq0fQBSyTFp8MXJhTWC4axsdp8S5RIdZBRvDndgZDZD&expires=5178096';
		$fileGetContentsWrapperMock->expects($this->at(0))
						->method('fetch')
						->with($this->equalTo($tokenUrl))
						->will($this->returnValue($fetchedAccessToken));

		$graphUrl = "https://graph.facebook.com/me?access_token=AAAFji9Iq0fQBSyTFp8MXJhTWC4axsdp8S5RIdZBRvDndgZDZD";
		$fetchedMeData = '{"id":"1234","name":"Sarah Jones","first_name":"Sarah","last_name":"Jones","link":"http:\\/\\/www.facebook.com\\/profile.php?id=1234","gender":"female","timezone":0,"locale":"","verified":true,"updated_time":"2020-12-05T23:13:36+0000"}';
		$fileGetContentsWrapperMock->expects($this->at(1))
						->method('fetch')
						->with($this->equalTo($graphUrl))
						->will($this->returnValue($fetchedMeData));

		$graphUrl = "https://graph.facebook.com/me/friends?access_token=AAAFji9Iq0fQBSyTFp8MXJhTWC4axsdp8S5RIdZBRvDndgZDZD";
		$fetchedFriendData = '{"data":[{"name":"Ryan","id":"12345"},{"name":"Melissa","id":"12346"},{"name":"John","id":"12347"},{"name":"Mallory","id":"12348"},{"name":"Lalana","id":"12349"}]}';
		$fileGetContentsWrapperMock->expects($this->at(2))
						->method('fetch')
						->with($this->equalTo($graphUrl))
						->will($this->returnValue($fetchedFriendData));
		
		$userFacebookIdMapperMock->expects($this->at(0))
						->method('exists')
						->with($this->equalTo("Sarah", "1234"))
						->will($this->returnValue(true)); //assuming already saved, not really worth testing, just constructor and save
		$facebookFriendMapperMock->expects($this->once())
						->method('saveAll');  //saveAll catches all AlreadyExists exceptions, so nothing to test here

		$facebookFriendMapperMock->expects($this->once())
						->method('findAllFacebookFriendsUids')
						->with("1234")
						->will($this->returnValue(array("Ryan", "Melissa", "John", "Mallory")));	


		$userFacebookIdMapperMock->expects($this->at(1))
						->method('find')
						->with($this->equalTo("Ryan"))
						->will($this->throwException(new DoesNotExistException('')));  //Test failure
		$userFacebookIdMelissa = new UserFacebookId();
		$userFacebookIdMelissa->setFacebookId("12346");
		$userFacebookIdMapperMock->expects($this->at(2))
						->method('find')
						->with($this->equalTo("Melissa"))
						->will($this->returnValue($userFacebookIdMelissa));
		$api->expects($this->at(1))
					->method('beginTransaction');
		$api->expects($this->at(2))
					->method('userExists')
					->with($this->equalTo("Melissa"))
					->will($this->returnValue(false));
		$api->expects($this->at(3))
					->method('commit');
		//There should be no more calls for this user

		$userFacebookIdJohn = new UserFacebookId();
		$userFacebookIdJohn->setFacebookId("12347");
		$userFacebookIdMapperMock->expects($this->at(3))
						->method('find')
						->with($this->equalTo("John"))
						->will($this->returnValue($userFacebookIdJohn));
		$api->expects($this->at(4))
					->method('beginTransaction');
		$api->expects($this->at(5))
					->method('userExists')
					->with($this->equalTo("John"))
					->will($this->returnValue(true));
		$friendshipMapperMock->expects($this->any()) //all of the remaining users will exist
					->method('exists')
					->will($this->returnValue(true)); //assuming saved, not really worth testing
		$facebookFriendMapperMock->expects($this->any())
					->method('deleteBoth');
		$api->expects($this->at(6))
					->method('commit');
		$userFacebookIdMallory = new UserFacebookId();
		$userFacebookIdMallory->setFacebookId("12348");
		$userFacebookIdMapperMock->expects($this->at(4))
						->method('find')
						->with($this->equalTo("Mallory"))
						->will($this->returnValue($userFacebookIdMallory));
		$api->expects($this->at(7))
					->method('beginTransaction');
		$api->expects($this->at(8))
					->method('userExists')
					->with($this->equalTo("Mallory"))
					->will($this->returnValue(true));
		$api->expects($this->at(9))
					->method('commit');

		$_REQUEST = array(
			'code' => 'mycode',
			'state' => 'myfakestate'
		);
		$_SESSION = array(
			'state' => 'myfakestate'
		);


		$response = $controller->facebookSync();

	}

	public function testSetSystemValueAnnotations(){
/*		$api = $this->getAPIMock();
		$controller = new FriendshipController($api, new Request(), null);	
		$methodName = 'setSystemValue';
		$annotations = array('Ajax');

		$this->assertAnnotations($controller, $methodName, $annotations);
*/	}


	public function testSetSystemValue(){
/*		$post = array('somesetting' => 'this is a test');
		$request = new Request(array(), $post);

		// create an api mock object
		$api = $this->getAPIMock();

		// expects to be called once with the method
		// setSystemValue('somesetting', 'this is a test')
		$api->expects($this->once())
					->method('setSystemValue')
					->with(	$this->equalTo('somesetting'),
							$this->equalTo('this is a test'));

		// we want to return the appname friends when this method
		// is being called
		$api->expects($this->any())
					->method('getAppName')
					->will($this->returnValue('friends'));

		$controller = new FriendshipController($api, $request, null);
		$response = $controller->setSystemValue(null);

		// check if the correct parameters of the json response are set
		$this->assertEquals($post, $response->getParams());
*/	}


}
