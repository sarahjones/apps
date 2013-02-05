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

		// thirdparty stuff
		$this->api->add3rdPartyScript('angular/angular');

		// your own stuff
		$this->api->addStyle('style');
		$this->api->addStyle('animation');

		$this->api->addScript('app');

		// example database access
		// check if an entry with the current user is in the database, if not
		// create a new entry
		try {

			$friends = $this->friendshipMapper->findAllFriendsByUser($this->api->getUserId());
		} catch (DoesNotExistException $e) {
			$friends = array();
		}


		try {
			$friendrequests = $this->friendshipRequestMapper->findAllRequesterFriendshipRequestsByUser($this->api->getUserId());
		} catch (DoesNotExistException $e) {
			$friendrequests = array();
		}
		try {
			$receivedfriendrequests = $this->friendshipRequestMapper->findAllRecipientFriendshipRequestsByUser($this->api->getUserId());
		} catch (DoesNotExistException $e) {
			$receivedfriendrequests = array();
		}
		$templateName = 'main';
		$params = array(
			'somesetting' => $this->api->getSystemValue('somesetting'),
			'friends' => $friends,
			'friendrequests' => $friendrequests,
			'receivedfriendrequests' => $receivedfriendrequests,
			'test' => $this->params('test')
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

		#surround with try block?
		#need to fix return
		if($this->friendshipRequestMapper->save($friendshipRequest)){
			return $this->renderJSON(array(true));
		}
		else {
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
			//need to cover this
		}

		if($this->friendshipRequestMapper->find($requester, $recipient)){
			$this->friendshipRequestMapper->delete($requester, $recipient);
			return $this->renderJSON(array(true));
		}
		else {
			alert("friendship does not exist");
			//need to cover this
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
		$success = false;
		\OCP\DB::beginTransaction();
		$requester = $this->params('acceptedFriend');	
		$currentUser = $this->api->getUserId();
error_log($currentUser . " " . $requester);
		try {
			$this->friendshipRequestMapper->find($requester, $currentUser);
			$this->friendshipRequestMapper->delete($requester, $currentUser);
			try {
				$this->friendshipMapper->find($currentUser, $requester);
				//expecting that it should not be found
error_log("no error thrown");
			}	
			catch (DoesNotExistException $e){
				$friendship = new Friendship();
				$friendship->setUid1($currentUser); 
				$friendship->setUid2($userUid);
				if($this->friendshipMapper->save($friendship)){
					$success = true;	
				}	
				else {
				}
			}
		}
		catch (DoesNotExistException $e) {
			//handle does not exist
			error_log("friendshiprequest not found");
		}
		#verify not in friends
		#add to friends
		if ($success)
			\OCP\DB::commit();
		else 
		#need to capture exception and change return value?
		return $this->renderJSON(array(true));
	}


	/** 
	 * @Ajax
	 * @IsSubAdminExemption
	 * @IsAdminExemption
	 *
	 * @brief creates a FriendshipRequest
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
	 * @brief creates a FriendshipRequest
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
}
