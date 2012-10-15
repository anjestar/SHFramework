<?php

define('FRAMEWORK_DIR', dirname(__FILE__));


require_once(FRAMEWORK_DIR . '/devinc.bad.php');
require_once(FRAMEWORK_DIR . '/devinc.log.php');
require_once(FRAMEWORK_DIR . '/devinc.string.php');
require_once(FRAMEWORK_DIR . '/devinc.cookie.php');

require_once(FRAMEWORK_DIR . '/devinc.action.php');
require_once(FRAMEWORK_DIR . '/devinc.smarty.php');

require_once(FRAMEWORK_DIR . '/weibo/devinc.mdb2.php');
require_once(FRAMEWORK_DIR . '/weibo/devinc.memcache.php');
