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

namespace OCA\MultiInstance\Cron;

/**
 * rsync with UCSB server
 */

//This should either be the ip address or the location name of the this village, depending on how we decide to configure these
$location = "village1";   
//ip or domain name of UCSB server
$server = "192.168.56.101";
//path to apps/multiinstance/cron/error.txt
$output = "/home/sarah/public_html/apps/multiinstance/cron/error.txt";

$cmd = "rsync --verbose --compress --rsh ssh \
      --recursive --times --perms --links --delete \
      --exclude "*~" \
      db_sync/ www-data@" . $server . "::db_sync_recv/" . $location . " >>" . $output " 2>&1";

$safe_cmd = escapeshellcmd($cmd);
exec($safe_cmd);

