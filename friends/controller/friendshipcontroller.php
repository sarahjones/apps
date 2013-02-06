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


class FriendshipController extends Controller {
	

	/**
	 * @param Request $request: an instance of the request
	 * @param API $api: an api wrapper instance
	 * @param ItemMapper $friendshipMapper: an itemwrapper instance
	 */
	public function __construct($api, $request, $friendshipMapper, $friendshipRequestMapper){
		parent::__construct($api, $request);
		$this->friendshipMapper = $friendshipMapper;
		$this->friendshipRequestMapper = $friendshipRequestMapper;
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


		$app_id = $this->api->getSystemValue('friends_fb_app_id');
		$app_secret = $this->api->getSystemValue('friends_fb_app_secret');
		$my_url = $this->api->getSystemValue('friends_fb_app_url');



   //session_start();


   $code = $_REQUEST["code"];
error_log("executed code");

   if(empty($code)) {
     // Redirect to Login Dialog
     $_SESSION['state'] = md5(uniqid(rand(), TRUE)); // CSRF protection
     $dialog_url = "https://www.facebook.com/dialog/oauth?client_id=" 
       . $app_id . "&redirect_uri=" . urlencode($my_url) . "&state="
       . $_SESSION['state'];

//     echo("<script> top.location.href='" . $dialog_url . "'</script>");
error_log("in if");
   }
   if($_SESSION['state'] && ($_SESSION['state'] === $_REQUEST['state'])) {
error_log("code" . $code);
     $token_url = "https://graph.facebook.com/oauth/access_token?"
       . "client_id=" . $app_id . "&redirect_uri=" . urlencode($my_url)
       . "&client_secret=" . $app_secret . "&code=" . $code;

     $response = file_get_contents($token_url);
     $params = null;
     parse_str($response, $params);
      $_SESSION['access_token'] = $params['access_token'];
error_log('token=' . $params['access_token']);
 $graph_url = "https://graph.facebook.com/me?access_token=" 
       . $params['access_token'];
error_log($graph_url);
     $user = json_decode(file_get_contents($graph_url));
error_log($user->name); 
error_log('id' . $user->id);
 $graph_url = "https://graph.facebook.com/me/friends?access_token=" 
       . $params['access_token'];
$friends = json_decode(file_get_contents($graph_url));
error_log("friends " . $friends->data[0]->name);
error_log("friends count " . count($friends->data));

   }
   else {
     echo("The state does not match. You may be a victim of CSRF.");
   }


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
			'test' => $this->params('test'),
			'fb_dialog_url' => $dialog_url //,
			//'fb_user' => $user->name
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

		if($this->friendshipRequestMapper->find($requester, $recipient)){
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
		$friendshipExists = true;
		$friendshipRequestExists = true;
		$deleted = false;
		$saved = false;

		$requester = $this->params('acceptedFriend');	
		$currentUser = $this->api->getUserId();
		\OCP\DB::beginTransaction();

		try {
			$this->friendshipRequestMapper->find($requester, $currentUser);
		}
		catch (DoesNotExistException $e) {
			$friendshipRequestExists = false;			
		}
		try {
			$this->friendshipMapper->find($currentUser, $requester);
		}	
		catch (DoesNotExistException $e){
			$friendshipExists = false;	
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
		try {
			$receivedfriendrequests = $this->friendshipRequestMapper->findAllRecipientFriendshipRequestsByUser($this->api->getUserId());
		} catch (DoesNotExistException $e) {
			$receivedfriendrequests = array();
		}
		try {
			$sentfriendrequests = $this->friendshipRequestMapper->findAllRequesterFriendshipRequestsByUser($this->api->getUserId());
		} catch (DoesNotExistException $e) {
			$sentfriendrequests = array();
		}
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
		try {

			$friends = $this->friendshipMapper->findAllFriendsByUser($this->api->getUserId());
		} catch (DoesNotExistException $e) {
			$friends = array();
		}
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
