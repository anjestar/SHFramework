<?php
////////////////////////////////////////////////////////////////////////////////
//
//  devinc.mbd2.php
//      将mdb2的操作封装起来。此文件实现了读写分离操作，调用者不用关心主从数据库。
//
//
//  s_db_plink()
//    返回主数据库链接（写操作）
//  
//  s_db_slink()
//    返回从数据库链接（读操作）
//  
//  s_db_close($db)
//    关闭数据库链接
//
//  s_db_list($sql)
//      返回列表数据（不从memcache缓存中获取数据）
//          $sql: select * from `%s_users` limit 100;
//
//  s_db_row($sql)
//      返回某行数据（不从memcache缓存中获取数据）
//          $sql: select * from `%s_users`;
//
//  s_db_one($sql)
//      返回某个字段值（不从memcache缓存中获取数据）
//          $sql: select `id` from `%s_users`;
//
//  s_db_exec($sql)
//      执行sql语句（update或insert，可以用%s_user:insert/update'代替）
//
//  s_db($action, $v1, $v2)
//      对数据操作（最常用语句），如有APP_DB_PREFIX常量，自动替换到表名前
//      1、插入数据
//          s_db("%s_user:insert", array("uid" => 1, "name" => "duanyong"))
//
//      2、更新数据
//          s_db("%s_user:update", array("id" => 1), array("uid" => 1, "name" => "duanyong"))
//
//      3、删除数据 XXX 慎用：除非表结构中status字段且可取负值 XXX
//          s_db("%s_user:delete", array("id" => 1))   //数组参数，指定表主键与值
//          s_db("%s_user:delete", 1)                  //数字参数，自动对应表主键
//
//  s_db_primary($sql, $id)
//      返回表主键对应的数据
//
//
//
////////////////////////////////////////////////////////////////////////////////

require_once('MDB2.php');


//主数据的链接（写操作）
function &s_db_plink() {
	$dsn = array(
        'phptype'  => "mysql",
        'username' => $_SERVER['SINASRV_DB4_USER'],
        'password' => $_SERVER['SINASRV_DB4_PASS'],
        'hostspec' => $_SERVER['SINASRV_DB4_HOST'],
        'port'     => $_SERVER['SINASRV_DB4_PORT'],
        'database' => $_SERVER['SINASRV_DB4_NAME'],
		'charset'  => 'utf8',
	);

	$db = MDB2::connect($dsn);
	if (MDB2::isError($db)) {
        die(s_error($db->getMessage()));
	}

	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);

	return $db;
}

//从数据库的链接（读操作）
function &s_db_slink() {
	$dsn = array(
        'phptype'  => "mysql",
        'username' => $_SERVER['SINASRV_DB4_USER_R'],
        'password' => $_SERVER['SINASRV_DB4_PASS_R'],
        'hostspec' => $_SERVER['SINASRV_DB4_HOST_R'],
        'port'     => $_SERVER['SINASRV_DB4_PORT_R'],
        'database' => $_SERVER['SINASRV_DB4_NAME_R'],
        'charset'  => 'utf8',
    );

	$db = MDB2::connect($dsn);
	if (MDB2::isError($db)) {
        die(s_error($db->getMessage()));
    }

    $db->setFetchMode(MDB2_FETCHMODE_ASSOC);

    return $db;
}

//Disconnecting a database
function s_db_close(&$dbh) {
	if (is_object($dbh)) {
        try {
            $dbh->disconnect();
        } catch (Exception $e) {
        }
	}

    $dbh = null;
}


//获取列表数据（不从memcache缓存中获取数据）
function s_db_list($sql) {
    if (s_bad_string($sql)
        || (false === ( $db = s_db_slink() ))
    ) {
        return false;
    }

    if (defined("APP_DB_PREFIX")
        && ( $count = substr_count($sql, '%s_') )
    ) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $sql = str_replace('%s_', APP_DB_PREFIX . '_', $sql, $count);
    }


    $ret = $db->queryAll($sql);

    s_db_close($db);

    if (PEAR::isError($ret)) {
        s_error($ret->getMessage());

        $ret = false;
    }

    return empty($ret) ? false : $ret;
}


//获取一行记录（不从memcache缓存中获取数据）
function s_db_row($sql) {
    if (s_bad_string($sql)
        || (false === ( $db = s_db_slink() ))
    ) {
        return false;
    }

    if (defined("APP_DB_PREFIX")
        && ( $count = substr_count($sql, '%s_') )
    ) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $sql = str_replace('%s_', APP_DB_PREFIX . '_', $sql, $count);
    }

    $ret = $db->queryRow($sql);
    s_db_close($db);

    if (PEAR::isError($ret)) {
        s_error($ret->getMessage());

        $ret = false;
    }


    return empty($ret) ? false : $ret;
}


//获取某个字段值（不从memcache缓存中获取数据）
function s_db_one($sql, $prefix=true) {
    if (s_bad_string($sql)
        || (false === ( $db = s_db_slink() ))
    ) {
        return false;
    }


    if (defined("APP_DB_PREFIX")
        && ( $count = substr_count($sql, '%s_') )
    ) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $sql = str_replace('%s_', APP_DB_PREFIX . '_', $sql, $count);
    }


    $ret = $db->queryOne($sql);
    s_db_close($db);

    if (PEAR::isError($ret)) {
        s_error($ret->getMessage());

        $ret = false;
    }

    return empty($ret) ? false : $ret;
}



//更新数据或插入数据（此处不清除缓存，不操作缓存）
function s_db_exec($sql) {
    if (s_bad_string($sql, $sql)
        || (false === ( $db = s_db_plink() ))
    ) {
        return false;
    }

    if (defined("APP_DB_PREFIX")
        && ( $count = substr_count($sql, '%s_') )
    ) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $sql = str_replace('%s_', APP_DB_PREFIX . '_', $sql, $count);
    }


    $ret = $db->exec($sql);


    if (PEAR::isError($ret)) {
        //记录失败
        s_error($ret->getMessage());

        $ret = false;
    }

    //是否执行成功
    if ($ret !== false
        && "insert" === substr($sql, 0, 6)
    ) {
        //插入成功，返回记录的主键
        $ret = $db->lastInsertID();
    }

    s_db_close($db);

    //返回插入记录的主键ID或者更新的行数
    return $ret;
}


//分析查询语句是何种类型（select, update, insert）
// s_db_desc("select * from `user` where `uid`=1 and `name`='sina' order by `uid` asc order by `name` group by 'sex' limit 22, 5;")
//return array(
//      "type"    => "select",
//      "table"   => "user",
//      "fileds"  => "*/user",
//      "where"   => array(
//                      "uid"  => 1,
//                      "name" => "sina",
//                  ),
//      "order"   => array(
//                      "uid"  => "asc"
//                      "name" => "desc"
//                  ),
//      "group"   => array("sex", "name"),
//      "list"    => array("sex", "name"),
//  )
//



// 数据操作
function s_db($table, &$v1, $v2=false) {
    if (s_bad_string($table)) {
        return s_err_arg();
    }


    if (defined("APP_DB_PREFIX")) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $table = sprintf($table, APP_DB_PREFIX, true);
    }

    ////////////////////////////////////////////////////////////////////////////////
    // s_db("%_user", uid)
    // s_db("%_user:insert", array("id" => 1, "name" => "张三"))
    // s_db("%_user:update", array("id" => 1, "name" => "张三"), array("name" => "duanyong"))
    // s_db("%_user:delete", id)

    // 对table分拆，得出表名和需要操作的类型
    $pos    = strrpos($table, ":");
    $action = $pos ? substr($table, $pos+1) : false;
    $table  = $pos ? substr($table, 0, $pos) : $table;

    $ret = false;

    if ($action === false) {
        // 按主键返回数据
        if (s_bad_id($v1)) {
            return s_err_arg();
        }

        $ret = s_db_primary($table, $v1);

    } else if ($action === "insert") {
        // 插入数据
        $ret = _s_db_insert($table, $v1);

    } else if ($action === "update") {
        // 更新
        $ret = _s_db_update($table, $v1, $v2);

    } else if ($action === "delete") {
        // 删除
        $ret = _s_db_delete($table, $v1);
    }


    return $ret;
}


// 返回主键对应的数据
function s_db_primary($table, $id) {
    if (s_bad_string($table)
        || s_bad_id($id)
    ) {
        return s_err_arg();
    }

    if (defined("APP_DB_PREFIX")) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $table = sprintf($table, APP_DB_PREFIX, true);
    }

    $sql = "select * from `{$table}` where `id`={$id} limit 1";

    return s_db_row($sql);
}


// 插入数据到数据库。其中$data已经包含了对应的主键
function _s_db_insert($table, &$data) {
    if (s_bad_string($table)
        || s_bad_array($data)
    ) {
        return s_err_arg();
    }

    if (isset($data["id"])) {
        //删除$data中的主键数据
        unset($data["id"]);
    }

    if (defined("APP_DB_PREFIX")) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $table = sprintf($table, APP_DB_PREFIX, true);
    }


    // 除去重复的值
    //$data = array_unique($data);

    // 将$data中的字段折出来(`name`, `age`, `sex`, `account`)
    $arr = array();
    $sql = "insert into `{$table}`";

    foreach (array_keys($data) as $key) {
        $arr[] = $key;
    }

    // (`name`, `age`, `sex`, `accuont`)
    $sql .= ' (`' . implode('`, `', $arr) . '`)';


    // 将$data中的数据组合起来（'duanyong', 12, true, 2456）
    $arr = array();

    foreach (array_values($data) as $value) {
        if (is_string($value)) {
            $arr[] = '"' . s_safe_value($value) . '"';

        } else if (is_int($value)) {
            $arr[] = $value;

        } else if (is_float($value)) {
            $arr[] = $value;

        } else if (is_double($value)) {
            $arr[] = $value;

        } else if (is_bool($value)) {
            $arr[] = ($value === true ? 1 : 0);

        } else {
            //非法类型，转成字符串
            $arr[] = '"' .  s_safe_value(strval($value)) . '"';
        }
    }

    // ('zhangsan', 22, true, 99)
    $sql .= ' value (' . implode(', ', $arr) . ')';

    return s_db_exec($sql);
}


// 更新数据，其中$v1是原始数据（包含主键字段id），$v2是需更新的字段，其中不能包括主键
function _s_db_update($table, &$v1, &$v2) {
    if (s_bad_string($table)
        || s_bad_array($v1)
        || s_bad_array($v2)

        //没有指定主键，更新失败
        || s_bad_id($v1["id"], $pid)
    ) {
        return s_err_arg("no primary key.");
    }

    if (defined("APP_DB_PREFIX")) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $table = sprintf($table, APP_DB_PREFIX, true);
    }

    if (isset($v2["id"])) {
        //防止更新主键
        unset($v2["id"]);
    }

    // 防止有重复的值
    $v2 = array_unique($v2);

    // 对$v1和$v2数据归类
    $values = array();
    foreach ($v2 as $key => $value) {
        if ($v1[$key] == $v2[$key]) {
            continue;
        }

        $values[] = "`{$key}`=" . ( is_string($value) ? '"' . s_safe_value($value) . '"' : $value );
    }

    $sql  = "update `{$table}` set " . implode(", ", $values) . " where `id`={$pid}";

    return s_db_exec($sql);
}



function _s_db_delete($table, $v1) {
    if (s_bad_string($table, $table, true)
        //是数组取主键值
        || !( $v1 = ( is_array($v1) && isset($v1["id"]) ) ? intval($v1["id"]) : $v1 )
        || s_bad_id($v1)
    ) {
        return s_err_arg();
    }


    if (defined("APP_DB_PREFIX")) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $table = sprintf($table, APP_DB_PREFIX, true);
    }
    $sql  = "update `{$table}` set `status`=-1 where `id`= {$v1}";

    return s_db_exec($sql);
}
