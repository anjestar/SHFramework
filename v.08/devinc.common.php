<?php

define('FRAMEWORK_DIR', dirname(__FILE__));

define('FRAMEWORK_DBPREFIX', 'sinash_framework');


require_once(FRAMEWORK_DIR . '/devinc.bad.php');
require_once(FRAMEWORK_DIR . '/devinc.log.php');
require_once(FRAMEWORK_DIR . '/devinc.string.php');

require_once(FRAMEWORK_DIR . '/devinc.action.php');
require_once(FRAMEWORK_DIR . '/devinc.smarty.php');
require_once(FRAMEWORK_DIR . '/devinc.http.php');
require_once(FRAMEWORK_DIR . '/devinc.sql.php');
require_once(FRAMEWORK_DIR . '/devinc.mdb2.php');
require_once(FRAMEWORK_DIR . '/devinc.memcache.php');
require_once(FRAMEWORK_DIR . '/devinc.safe.php');

require_once(FRAMEWORK_DIR . '/devinc.user.php');
require_once(FRAMEWORK_DIR . '/devinc.weibo.php');


//业务需要
require_once(FRAMEWORK_DIR . '/devcls.ssocookie.php');
