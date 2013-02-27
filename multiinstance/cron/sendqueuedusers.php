<?php

require './lib/cronsend.php';
require './../appframework/core/api.php';

$cronSend = new CronSend(new API('multiinstance'));
$cronSend->dump_queued_users();
