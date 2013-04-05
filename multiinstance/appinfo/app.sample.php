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

namespace OCA\MultiInstance;


\OCP\App::registerAdmin('multiinstance', 'admin/settings');

//No Nav Entry because this app does not have an UI

//This instance's location settings
//location name should be in Linux file format (no spaces, etc), as it will be used as a folder name
\OCP\Config::setAppValue('multiinstance', 'location', 'Macha');
//IP address
\OCP\Config::setAppValue('multiinstance', 'ip', '192.168.56.102');

//ip or domain name of UCSB server (or whatever the central server is)
\OCP\Config::setAppValue('multiinstance', 'centralServerIP', '192.168.56.101');
\OCP\Config::setAppValue('multiinstance', 'centralServer', 'UCSB');


//path to apps/multiinstance/cron/error.txt
$errorLog =  "/home/sarah/public_html/apps/multiinstance/cron/error.txt";
\OCP\Config::setAppValue('multiinstance', 'cronErrorLog', $errorLog);
//path to apps/multiinstance/db_sync_recv
$dbSyncRecvPath = "/home/sarah/public_html/apps/multiinstance/db_sync_recv";
\OCP\Config::setAppValue('multiinstance', 'dbSyncRecvPath', $dbSyncRecvPath);
$dbSyncFolder = "/home/sjones/public_html/dev/apps/multiinstance/db_sync/";
\OCP\Config::setAppValue('multiinstance', 'dbSyncPath', $dbSyncFolder);



//Linux user to use for nc (netcat)
$ncPort = "30000"
\OCP\Config::setAppValue('multiinstance', 'ncUser', "sarah");
\OCP\Config::setAppValue('multiinstance', 'ncLocalPort', $ncPort);   //the local, client port (on UCSB/central server)
\OCP\Config::setAppValue('multiinstance', 'ncServerPort', $ncPort);  //the port the nc server is listening on (on village server) ****NOTE: should be the same as ncLocalPort, at least I think so.  :D
\OCP\Config::setAppValue('multiinstance', 'ncMiddlePort', "30005");  //the tunneling port (will be on nc server) (on village server)




\OCP\Util::connectHook('OC_User', 'post_createUser', 'OCA\MultiInstance\Lib\Hooks', 'createUser');
\OCP\Util::connectHook('OC_User', 'post_setPassword', 'OCA\MultiInstance\Lib\Hooks', 'updateUser');
