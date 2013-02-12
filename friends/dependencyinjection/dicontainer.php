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

namespace OCA\Friends\DependencyInjection;

use OCA\AppFramework\DependencyInjection\DIContainer as BaseContainer;

use OCA\Friends\Controller\FriendshipController as FriendshipController;
use OCA\Friends\Controller\SettingsController as SettingsController;
use OCA\Friends\Db\FriendshipMapper as FriendshipMapper;
use OCA\Friends\Db\FriendshipRequestMapper as FriendshipRequestMapper;
use OCA\Friends\Db\UserFacebookIdMapper as UserFacebookIdMapper;
use OCA\Friends\Db\FacebookFriendMapper as FacebookFriendMapper;

use OCA\Friends\Core\FileGetContentsWrapper as FileGetContentsWrapper;
use OCA\Friends\Core\FriendsAPI as FriendsAPI;

class DIContainer extends BaseContainer {


	/**
	 * Define your dependencies in here
	 */
	public function __construct(){
		// tell parent container about the app name
		parent::__construct('friends');

		//Overwriting API 
		$this['API'] = $this->share(function($c){
			return new FriendsAPI($c['AppName']);
		});

		/**
		 * Delete the following twig config to use ownClouds default templates
		 */
		// use this to specify the template directory
		$this['TwigTemplateDirectory'] = __DIR__ . '/../templates';

		// if you want to cache the template directory, add this path
		$this['TwigTemplateCacheDirectory'] = null;		

		/** 
		 * CONTROLLERS
		 */
		$this['FriendshipController'] = $this->share(function($c){
			return new FriendshipController($c['API'], $c['Request'], $c['FriendshipMapper'], $c['FriendshipRequestMapper'], $c['UserFacebookIdMapper'], $c['FacebookFriendMapper']);
		});

		$this['SettingsController'] = $this->share(function($c){
			return new SettingsController($c['API'], $c['Request']);
		});


		/**
		 * MAPPERS
		 */
		$this['FriendshipMapper'] = $this->share(function($c){
			return new FriendshipMapper($c['API']);
		});
		$this['FriendshipRequestMapper'] = $this->share(function($c){
			return new FriendshipRequestMapper($c['API']);
		});
		$this['UserFacebookIdMapper'] = $this->share(function($c){
			return new UserFacebookIdMapper($c['API']);
		});
		$this['FacebookFriendMapper'] = $this->share(function($c){
			return new FacebookFriendMapper($c['API']);
		});
		
 
	}
}

