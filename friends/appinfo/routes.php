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

namespace OCA\Friends;

use \OCA\AppFramework\App as App;

use \OCA\Friends\DependencyInjection\DIContainer as DIContainer;


/*************************
 * Define your routes here
 ************************/

/**
 * Normal Routes
 */
$this->create('friends_index', '/')->action(
	function($params){
		App::main('FriendshipController', 'index', $params, new DIContainer());
	}
);

$this->create('friends_index_param', '/test/{test}')->action(
	function($params){
		App::main('FriendshipController', 'index', $params, new DIContainer());
	}
);

$this->create('friends_index_redirect', '/redirect')->action(
	function($params){
		App::main('FriendshipController', 'redirectToIndex', $params, new DIContainer());
	}
);

/**
 * Ajax Routes
 */
$this->create('friends_ajax_setsystemvalue', '/setsystemvalue')->post()->action(
	function($params){
		App::main('FriendshipController', 'setSystemValue', $params, new DIContainer());
	}
);


$this->create('friends_ajax_createFriendshipRequest', '/friendshipRequest')->post()->action(
	function($params){
		App::main('FriendshipController', 'createFriendshipRequest', $params, new DIContainer());
	}
);

$this->create('friends_ajax_removeFriendshipRequest', '/removeFriendshipRequest')->post()->action(
	function($params){
		App::main('FriendshipController', 'removeFriendshipRequest', $params, new DIContainer());
	}
);

$this->create('friends_ajax_acceptFriendshipRequest', '/acceptFriendshipRequest')->post()->action(
	function($params){
		App::main('FriendshipController', 'acceptFriendshipRequest', $params, new DIContainer());
	}
);

$this->create('friends_ajax_getFriendshipRequests', '/friendshipRequests')->post()->action(
	function($params){
		App::main('FriendshipController', 'getFriendshipRequests', $params, new DIContainer());
	}
);

$this->create('friends_ajax_getFriendships', '/friendships')->post()->action(
	function($params){
		App::main('FriendshipController', 'getFriendships', $params, new DIContainer());
	}
);
