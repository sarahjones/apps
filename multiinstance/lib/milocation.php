<?php
/**
 * ownCloud - Multi Instance
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

namespace OCA\MultiInstance\Lib;

use OCA\MultiInstance\Core\MultiInstanceAPI;
use OCA\MultiInstance\Db\LocationMapper;
use OCA\MultiInstance\DependencyInjection\DIContainer;

/**
 * This is a static library methods for MultiInstance app.
 */
class MILocation{

	static public function getLocations() {
		$api = new MultiInstanceAPI('multiinstance');
		$locationMapper = new LocationMapper($api);
		return $locationMapper->findAll();
	}


	static public function uidContainsLocation($uid, $locationMapper=null) {
		if (strpos($uid,'@')) {
			$pattern = '/@(?P<location>[^@]+)$/';
			$matches = array();
			if (preg_match($pattern, $uid, $matches) === 1) { //must use === for this function (according to documentation)
				if ($locationMapper !== null) { //For testability 
					$lm = $locationMapper;
				} 
				else {  
					$di = new DIContainer();
					$lm = $di['LocationMapper']; 
				}
				return $lm->existsByLocation($matches['location']); 
			}
		}
		return false;
	}

	static public function uidContainsThisLocation($uid, $apiForTest=null) {
		if ($apiForTest === null) {
			$api = new MultiInstanceAPI('multiinstance');
		}
		else {
			$api = $apiForTest;
		}
		$location = $api->getAppValue('location');
		if (strpos($uid, '@')) {
			$pattern = '/@' . $location . '$/';
			if (preg_match($pattern, $uid) === 1) { //must use === for this function (according to documentation)
				return true;
			}
		}
		return false;
	}


	static public function userExistsAtCentralServer($uid) {

	}
}
