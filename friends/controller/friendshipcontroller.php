<?php

/**
* ownCloud - App Template Example
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

namespace OCA\Friends\Controller;

use OCA\AppFramework\Controller\Controller as Controller;
use OCA\AppFramework\Db\DoesNotExistException as DoesNotExistException;
use OCA\AppFramework\Http\RedirectResponse as RedirectResponse;

use OCA\Friends\Db\Friendship as Friendship;
use OCA\Friends\Db\FriendshipRequest as FriendshipRequest;
use OCA\Friends\Db\UserFacebookId as UserFacebookId;
use OCA\Friends\Db\FacebookFriend as FacebookFriend;


class FriendshipController extends Controller {
	

	/**
	 * @param Request $request: an instance of the request
	 * @param API $api: an api wrapper instance
	 * @param ItemMapper $friendshipMapper: an itemwrapper instance
	 */
	public function __construct($api, $request, $friendshipMapper, $friendshipRequestMapper, $userFacebookIdMapper, $facebookFriendsMapper){
		parent::__construct($api, $request);
		$this->friendshipMapper = $friendshipMapper;
		$this->friendshipRequestMapper = $friendshipRequestMapper;
		$this->userFacebookIdMapper = $userFacebookIdMapper;
		$this->facebookFriendsMapper = $facebookFriendsMapper;
		
		$this->app_id = $this->api->getSystemValue('friends_fb_app_id');
		$this->app_secret = $this->api->getSystemValue('friends_fb_app_secret');
		$this->my_url = $this->api->getSystemValue('friends_fb_app_url');
	}


	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * Redirects to the index page
	 */
	public function redirectToIndex(){
		$url = $this->api->linkToRoute('friends_index');
		return new RedirectResponse($url);
	}


	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @brief renders the index page
	 * @return an instance of a Response implementation
	 */
	public function index(){


		// thirdparty stuff
		$this->api->add3rdPartyScript('angular/angular');

		// your own stuff
		$this->api->addStyle('style');
		$this->api->addStyle('animation');

		$this->api->addScript('app');

		// example database access
		// check if an entry with the current user is in the database, if not
		// create a new entry
		$templateName = 'main';
		$params = array(
			'somesetting' => $this->api->getSystemValue('somesetting'),
			'test' => $this->params('test')
		);
		return $this->render($templateName, $params);
	}


	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @brief renders the facebook page
	 * @return an instance of a Response implementation
	 */
	public function facebookSync(){

		/*	Start Facebook Code 	*/
		//Set by Facebook response
		$code = $_REQUEST["code"];

		// Redirect to Login Dialog
		if(empty($code)) {
			$_SESSION['state'] = md5(uniqid(rand(), TRUE)); // CSRF protection

			$dialog_url = "https://www.facebook.com/dialog/oauth?client_id=" 
			. $this->app_id . "&redirect_uri=" . urlencode($this->my_url) . "&state="
			. $_SESSION['state'];
		}

		//Have permission
		if($_SESSION['state'] && ($_SESSION['state'] === $_REQUEST['state'])) {
			$token_url = "https://graph.facebook.com/oauth/access_token?"
				. "client_id=" . $this->app_id . "&redirect_uri=" . urlencode($this->my_url)
				. "&client_secret=" . $this->app_secret . "&code=" . $code;

			$response = file_get_contents($token_url); //Get access token
			$params = null;
			parse_str($response, $params);
			$_SESSION['access_token'] = $params['access_token'];

			$graph_url = "https://graph.facebook.com/me?access_token=" 
					. $params['access_token'];
			$user = json_decode(file_get_contents($graph_url)); //Get user info
			$userFacebookId = new UserFacebookId();
			$userFacebookId->setUid($this->api->getUserId());
			$userFacebookId->setFacebookId($user->id);
			$this->userFacebookIdMapper->save($userFacebookId);
	
			$graph_url = "https://graph.facebook.com/me/friends?access_token=" 
					. $params['access_token'];
			$friends = json_decode(file_get_contents($graph_url)); //Get user's friends
			$facebookFriends = FacebookFriend::createFromList($friends->data, $this->api->getUserId());
			$this->facebookFriendsMapper->saveAll($facebookFriends);

		}
		else {
			error_log("State does not match for Facebook auth");
			echo("The state does not match. You may be a victim of CSRF.");
		}
		/* 	End Facebook Code	*/


		// thirdparty stuff
		$this->api->add3rdPartyScript('angular/angular');

		// your own stuff
		$this->api->addStyle('style');
		$this->api->addStyle('animation');

		$this->api->addScript('app');

		$templateName = 'facebook';
		$params = array(
			'fb_dialog_url' => $dialog_url
		);
		return $this->render($templateName, $params);

	}



	/**
	 * @Ajax
	 *
	 * @brief sets a global system value
	 * @param array $urlParams: an array with the values, which were matched in 
	 *                          the routes file
	 */
	public function setSystemValue(){
		$value = $this->params('somesetting');
		$this->api->setSystemValue('somesetting', $value);

		$params = array(
			'somesetting' => $value
		);

		return $this->renderJSON($params);
	}

	/** 
	 * @Ajax
	 * @IsSubAdminExemption
	 * @IsAdminExemption
	 *
	 * @brief creates a FriendshipRequest
	 * @param 
	 */
	public function createFriendshipRequest(){
		$recipientId = $this->params('recipient');

		$friendshipRequest = new FriendshipRequest();
		$friendshipRequest->setRequester($this->api->getUserId()); 
		$friendshipRequest->setRecipient($recipientId);

		//TODO: error handling
		if($this->friendshipRequestMapper->save($friendshipRequest)){
			//TODO: return something useful
			return $this->renderJSON(array(true));
		}
		else {
			//TODO: return something useful
			return $this->renderJSON(array(false));
		}	
	}

	/** 
	 * @Ajax
	 * @IsSubAdminExemption
	 * @IsAdminExemption
	 *
	 * @brief removes a FriendshipRequest
	 * @param 
	 */
	public function removeFriendshipRequest(){
		$userUid = $this->params('userUid');
		$sentOrReceived = $this->params('sentOrReceived');

		if ($sentOrReceived === 'sent'){
			$recipient = $userUid;
			$requester = $this->api->getUserId();
		}
		else if ($sentOrReceived === 'received'){
			$recipient = $this->api->getUserId();
			$requester = $userUid;
		}
		else {
			//TODO: error handling
		}

		if($this->friendshipRequestMapper->exists($requester, $recipient)){
			$this->friendshipRequestMapper->delete($requester, $recipient);
			//TODO: return something useful
			return $this->renderJSON(array(true));
		}
		else {
			//TODO: error handling
			error_log("cannot find friendshiprequest for removeFriendshipRequest");
		}
		

	}

		
	/** 
	 * @Ajax
	 * @IsSubAdminExemption
	 * @IsAdminExemption
	 *
	 * @brief converts FriendshipRequest into a friendship
	 * @param 
	 */
	public function acceptFriendshipRequest(){
		$friendshipExists = false;
		$friendshipRequestExists = false;
		$deleted = false;
		$saved = false;

		$requester = $this->params('acceptedFriend');	
		$currentUser = $this->api->getUserId();
		\OCP\DB::beginTransaction();

		if ($this->friendshipRequestMapper->exists($requester, $currentUser)){
			$friendshipRequestExists = true;			
		}
		if ($this->friendshipMapper->exists($currentUser, $requester)){
			$friendshipExists = true;	
		}	

		if ($friendshipRequestExists && !$friendshipExists){
			if($this->friendshipRequestMapper->delete($requester, $currentUser))
				$deleted = true;
			$friendship = new Friendship();
			$friendship->setUid1($currentUser); 
			$friendship->setUid2($requester);
			if($this->friendshipMapper->save($friendship)){
				$saved = true;	
			}	
		}
		if (!$deleted || !$saved){
			//TODO: change this to an exception with more detail
			error_log("Error in acceptFriendshipRequest");
		} 
		\OCP\DB::commit();
		//TODO: change this to return something useful
		return $this->renderJSON(array(true));
	}


	/** 
	 * @Ajax
	 * @IsSubAdminExemption
	 * @IsAdminExemption
	 *
	 * @brief gets all FriendshipRequests for the current user
	 * @param 
	 */
	public function getFriendshipRequests(){
		$receivedfriendrequests = $this->friendshipRequestMapper->findAllRecipientFriendshipRequestsByUser($this->api->getUserId());
		$sentfriendrequests = $this->friendshipRequestMapper->findAllRequesterFriendshipRequestsByUser($this->api->getUserId());

		$params = array(
			'receivedFriendshipRequests' => $receivedfriendrequests,
			'sentFriendshipRequests' => $sentfriendrequests
		);
		return $this->renderJSON($params);
		
	}


	/** 
	 * @Ajax
	 * @IsSubAdminExemption
	 * @IsAdminExemption
	 *
	 * @brief gets Friendships for the current user
	 * @param 
	 */
	public function getFriendships(){
		$friends = $this->friendshipMapper->findAllFriendsByUser($this->api->getUserId());
		$params = array(
			'friendships' => $friends
		);
		return $this->renderJSON($params);
	}

	/** 
	 * @Ajax
	 * @IsSubAdminExemption
	 * @IsAdminExemption
	 *
	 * @brief deletes a Friendship for the current user
	 * @param 
	 */
	public function removeFriendship(){
		$userUid = $this->params('friend');
		$currentUser = $this->api->getUserId();
		$this->friendshipMapper->delete($userUid, $currentUser);

error_log("deleted");
		//TODO: return useful info
		return $this->renderJSON(array(true));

	}
}
