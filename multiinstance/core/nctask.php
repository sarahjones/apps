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

namespace OCA\MultiInstance\Core;

use \OCP\OC_User;


class NCTask {
	

	private $api; 
	private $receivedUserMapper;
	private $userUpdateMapper;
	private $locationMapper;
	
	/**
	 * @param API $api: an api wrapper instance
	 */
	public function __construct($api, $receivedUserMapper, $userUpdateMapper, $locationMapper){
		$this->api = $api;
		$this->receivedUserMapper = $receivedUserMapper;
		$this->userUpdateMapper = $userUpdateMapper;
		$this->locationMapper = $locationMapper;
	}

	public function processLine($line) {
		$pattern = '/^\[(?<label>[^\]]+)\]/';
		$matches = array();
		if (preg_match($pattern, $line, $matches) === 1) {
			switch($matches['label']){
				case "UACK":  //(queued) user ack
					break;
				case "UQUERY": //user query
					break;
				case "UQUERYR": //usery query response
					break;
				default:
					$api->log("Invalid option {$matches['label']}");
			}
		}
	}

	/**
	 *
	 * source: http://www.g-loaded.eu/2006/11/06/netcat-a-couple-of-useful-examples/
	 * This command will create an ssh tunnel (encrypted from localhost with $localPort 
	 * to $ip with $middlePort and unencrypted from $ip $middlePort to $ip $serverPort) 
         * in the background and send the $ackedList string to the nc server on $ip $serverPort.
	 * The ssh tunnel must execute a command (the source chose sleep).  At the same time
	 * nc will run through the tunnel.
	 */
	public function send($string, $ip) {
		$output = $this->api->getAppValue('cronErrorLog');
		$user = $this->api->getAppValue('ncUser');
		$localPort = $this->api->getAppValue('ncLocalPort'); //the local, client port
		$middlePort = $this->api->getAppValue('ncMiddlePort'); //the tunneling port (will be on Server)
		$serverPort = $this->api->getAppValue('ncServerPort'); //the port the nc server is listening on

		$this->api->exec("( ssh -f -L {$localPort}:{$ip}:{$middlePort} {$user}@{$ip} sleep 1;  \
			echo \"{$string}\" |  nc -w 1 {$ip} {$serverPort} ) > " . $output .  2>&1 &");	

	}
}
