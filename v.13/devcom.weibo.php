<?php

define('FRAMEWORK_DIR', dirname(__FILE__));


require_once(FRAMEWORK_DIR . '/devinc.bad.php');
require_once(FRAMEWORK_DIR . '/devinc.log.php');
require_once(FRAMEWORK_DIR . '/devinc.cookie.php');

require_once(FRAMEWORK_DIR . '/devinc.smarty.php');
require_once(FRAMEWORK_DIR . '/devinc.safe.php');

//引用微博脚本
require_once(FRAMEWORK_DIR . '/weibo/devinc.string.php');
require_once(FRAMEWORK_DIR . '/weibo/devinc.action.php');
require_once(FRAMEWORK_DIR . '/weibo/devinc.http.php');
require_once(FRAMEWORK_DIR . '/weibo/devinc.mdb2.php');
require_once(FRAMEWORK_DIR . '/weibo/devinc.user.php');
require_once(FRAMEWORK_DIR . '/weibo/devinc.weibo.php');
require_once(FRAMEWORK_DIR . '/weibo/devinc.memcache.php');


//业务需要
require_once(FRAMEWORK_DIR . '/weibo/devcls.ssocookie.php');
