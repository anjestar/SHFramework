<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.bingo.php
//	将mdb2的操作封装起来。此文件实现了读写分离操作，调用者不用关心主从数据库。
//
//
//  s_db_primary($sql, $id)
//      返回表主键对应的数据
//
//  s_db_where($table, $where)
//      根据条件数组返回列表数据
//          s_db_where("user", array("`name`='duanyong'", "`age`>=24" "limit" => "0, 3", "order"=> "`uid` desc, `age` asc"))
//
//
////////////////////////////////////////////////////////////////////////////////


//正常标志
define("FRAMEWORK_BINGO_STATUS", 0);
//未中奖标志
define("FRAMEWORK_BINGO_NO", 1);
//已中奖标志
define("FRAMEWORK_BINGO_YES", 2);
//重复中奖标志
define("FRAMEWORK_BINGO_REPEAT", 2);


function s_bingo_list_by_page($page) {
    if (s_bad_string($page)) {
        return s_log_arg();
    }

    $sql = sprintf("select * from `%s_bingo` where `key`='%s' and `page`='%s' limit 2000", FRAMEWORK_DBPREFIX, APP_BINGO_KEY, $page, true);

    return s_db_list($sql);
}


function s_bingo_list_by_user_page(&$user, $page) {
    if (s_bad_array($user)
        || s_bad_string($page)
    ) {
        return s_log_arg();
    }

    $sql = sprintf("select * from `%s_bingo` where `key`='%s' and `page`='%s' and `uid`='%d' and `status`=%d limit 2000", FRAMEWORK_DBPREFIX, APP_BINGO_KEY, $page, $user['id'], FRAMEWORK_BINGO_YES, true);

    return s_db_list($sql, false);
}


function s_bingo_config() {
    if (defined('APP_BINGO_KEY')) {
        return false;
    }

    return s_db_row(sprintf("select * from %_bingo_config where `key`='%s' limit 1", FRAMEWORK_DBPREFIX, APP_BINGO_KEY));
}


function s_bingo_odds(&$config) {
    if (s_bad_array($config)) {
        return false;
    }

    if ($config['odds']) {
        //太大了，基本不可能被命中，所以将值改小，提高命中率
        $config['odds'] = 25;
    }

    return rand(1, $config['odds']) == $config['odds'];
}


function s_bingo_go_by_user(&$user) {
    if (s_bad_array($user)
        || false === ( $conf = s_bingo_config() )
    ) {
        return s_log_arg();
    }

    if ($conf['repeat'] == 0) {
        //同一个用户不可重复
        if (( $bingo = s_db_row(sprintf("select * from `%s_bingo` where `key`='%s' and `uid`='%d' and `status`=%d limit 1", FRAMEWORK_DBPREFIX, $conf['key'], $user['id'], FRAMEWORK_BINGO_YES))) ) {
            //已中奖
            return $bingo;
        }
    }


    //查看当天或者以前是否有奖可拿
    $sql = sprintf("select * from `%s_bingo` where `key`='%s' and `status`=%d and `time`<=%d", 
        FRAMEWORK_DBPREFIX, $conf['key'], FRAMEWORK_BINGO_STATUS, strtotime("today"));

    if (s_bingo_odds($config)
        && false !== ( $bingo = s_db_row($sql) )
    ) {
        //有奖可拿
        $v2 = array(
            'uid'       => $user['uid'],
            'status'    => FRAMEWORK_BINGO_YES,
        );

        if (s_db(FRAMEWORK_DBPREFIX . "_bingo:update", $bingo, $v2)) {
            //更新成功
            return s_db(FRAMEWORK_DBPREFIX . "_bingo", $bingo['id']);
        }
    }

    //未中奖
    return false;
}

function s_bingo_go_by_user_page(&$user, &$page) {
    if (s_bad_array($user)
        || s_bad_string($page)
        || false === ( $conf = s_bingo_config() )
    ) {
        return s_log_arg();
    }


    if ($conf['repeat'] == 0) {
        //同一个用户不可重复
        if (( $bingo = s_db_row(sprintf("select * from `%s_bingo` where `key`='%s' and `uid`='%d' and `page`='%s' and `status`=%d limit 1", 
            FRAMEWORK_DBPREFIX, $conf['key'], $user['id'], $page, FRAMEWORK_BINGO_YES))) ) {
            //已中奖
            $bingo['status'] = 

            return $bingo;
        }
    }


    //查看当天或者当天前是否有奖可拿
    $sql = sprintf("select * from `%s_bingo` where `key`='%s' and `page`='%s' and `time`<=%d and `status`=%d limit 1", 
        FRAMEWORK_DBPREFIX, $conf['key'], $page, strtotime("today"), FRAMEWORK_BINGO_STATUS);

    if (s_bingo_odds($config)
        && false !== ( $bingo = s_db_row($sql) )
    ) {
        $time = s_action_time();

        //有奖可拿
        $v2 = array(
            'uid'       => $user['uid'],
            'page'      => $page,
            'status'    => FRAMEWORK_BINGO_YES,

            'time'      => $time,
            'fdate'     => date('Y-m-d', $time),
            'ftime'     => date('Y-m-d H:i:s', $time),
        );

        if (s_db(FRAMEWORK_DBPREFIX . "_bingo:update", $bingo, $v2)) {
            //更新成功
            return s_db(FRAMEWORK_DBPREFIX . "_bingo", $bingo['id']);
        }
    }

    //未中奖
    return false;
}
