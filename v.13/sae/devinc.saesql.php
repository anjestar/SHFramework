<?php
////////////////////////////////////////////////////////////////////////////////
// devinc.mysql.php
//	数据库mysql相关的操作
//	    主要功能是提供符合mysql的连接池，断开以及如何执行sql语句的接口
//
//	    s_db($table, $v1, $v2=false)
//	        数据库操作函数，下面几种情况
//
//		s_db("user", 1)
//		    根据主键查询表数据
//
//		s_db("user:insert", array("name" => "test"))
//		    返回插入的主键ID(注意:数据中不能有表的主键）
//
//		s_db("user:update", array("uid" => 3, "name" => "张三", "age" => true), array("name" => "duanyong"))
//		    更新数据到表中(注意:插入的数据需要指定主键)
//	
//	    s_mysql_conn()
//	        将数据合并到模板中，供模板输出
//
//
////////////////////////////////////////////////////////////////////////////////


//主数据的链接（写操作）
function &s_db_plink() {
	$dsn = array(
        'phptype'  => "mysql",
        'username' => SAE_MYSQL_USER,
        'password' => SAE_MYSQL_PASS,
        'hostspec' => SAE_MYSQL_HOST_M,
        'database' => SAE_MYSQL_DB,
        'dataport' => SAE_MYSQL_PORT,
		'charset'  => 'utf8',
	);

    if (false === ( $conn = mysql_connect($dsn['hostspec'] . ':' . $dsn['dataport'], $dsn["username"], $dsn["password"]) )
        || false === mysql_select_db($dsn["database"], $conn)
        || false === mysql_query("SET NAMES '{$dsn['charset']}'", $conn)
    ) {
        return false;
    }

    return $conn;
}

//从数据库的链接（读操作）
function &s_db_slink() {
	$dsn = array(
        'phptype'  => "mysql",
        'username' => SAE_MYSQL_USER,
        'password' => SAE_MYSQL_PASS,
        'hostspec' => SAE_MYSQL_HOST_S,
        'database' => SAE_MYSQL_DB,
        'dataport' => SAE_MYSQL_PORT,
		'charset'  => 'utf8',
	);


    if (false === ( $conn = mysql_connect($dsn['hostspec'] . ':' . $dsn['dataport'], $dsn["username"], $dsn["password"]) )
        || false === mysql_select_db($dsn["database"], $conn)
        || false === mysql_query("SET NAMES '{$dsn['charset']}'", $conn)
    ) {
        return false;
    }

    return $conn;
}


//Disconnecting a database
function s_db_close(&$link) {
    try {
        mysql_close($link);
    } catch (Exception $e) {
    }

    $link = null;
}



//更新数据或插入数据（此处不清除缓存，不操作缓存）
function s_db_exec($sql) {
    if (s_bad_string($sql, $sql, true)
        || (false === ( $link = s_db_plink() ))
    ) {
        return false;
    }

    if (defined("APP_DB_PREFIX")
        && ( $count = substr_count($sql, '%s_') )
    ) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $sql = str_replace('%s_', APP_DB_PREFIX . '_', $sql, $count);
    }


    if (false === ( $ret = mysql_query($sql, $link) )) {
        die(s_log(mysql_error()));
    }

    //是否执行成功
    if ($ret !== false
        && "insert" === substr($sql, 0, 6)
    ) {
        //插入成功，返回记录的主键
        $ret = mysql_insert_id();
    }

    s_db_close($link);

    //返回插入记录的主键ID或者更新的行数
    return $ret;
}


// 返回列表
function s_db_list($sql) {
    if (s_bad_string($sql, $sql, true)
        || (false === ( $link = s_db_slink() ))
    ) {
        return false;
    }


    if (defined("APP_DB_PREFIX")
        && ( $count = substr_count($sql, '%s_') )
    ) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $sql = str_replace('%s_', APP_DB_PREFIX . '_', $sql, $count);
    }


    if (false === ( $reader = mysql_query($sql, $link) )) {
        die(s_log(mysql_error()));
    }


    // 得到资源后，取得对应的数据
    $rows = array();
    while ($row = mysql_fetch_assoc($reader)) {
        $rows[] = $row;
    }

    // 释放资源文件
    mysql_free_result($reader);

    s_db_close($link);

    return $rows;
}


// 返回行数据
function s_db_row($sql) {
    if (s_bad_string($sql, $sql, true)
        || (false === ( $link = s_db_slink() ))
    ) {
        return false;
    }


    if (defined("APP_DB_PREFIX")
        && ( $count = substr_count($sql, '%s_') )
    ) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $sql = str_replace('%s_', APP_DB_PREFIX . '_', $sql, $count);
    }


    if (false === ( $reader = mysql_query($sql, $link) )) {
        die(s_log(mysql_error()));
    }


    // 得到资源后，取得对应的数据
    $row = mysql_fetch_assoc($reader);
    
    // 释放资源文件
    mysql_free_result($reader);

    s_db_close($link);

    return $row;
}


// 返回某列数据
function s_db_one($sql) {
    if (s_bad_string($sql, $sql, true)
        || (false === ( $link = s_db_slink() ))
    ) {
        return false;
    }


    if (defined("APP_DB_PREFIX")
        && ( $count = substr_count($sql, '%s_') )
    ) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $sql = str_replace('%s_', APP_DB_PREFIX . '_', $sql, $count);
    }


    if (false === ( $reader = mysql_query($sql, $link) )) {
        die(s_log(mysql_error()));
    }


    // 得到资源后，取得对应的数据
    $row = mysql_fetch_assoc($reader);
    
    // 释放资源文件
    mysql_free_result($reader);

    s_db_close($link);

    return current($row);
}


// 数据操作
function s_db($table, &$v1, &$v2=false) {
    if (s_bad_string($table)) {
        return s_log_arg();
    }


    ////////////////////////////////////////////////////////////////////////////////
    // s_db("user", uid)
    // s_db("user:insert", array("uid" => 1, "name" => "张三"))
    // s_db("user:update", array("uid" => 1, "name" => "张三"), array("name" => "duanyong"))
    // s_db("user:delete", uid)

    // 对table分拆，得出表名和需要操作的类型
    $pos    = strrpos($table, ":");
    $action = $pos ? substr($table, $pos+1) : false;
    $table  = $pos ? substr($table, 0, $pos) : $table;

    $ret = false;

    if ($action === false) {
        // 按主键返回数据
        if (s_bad_id($v1)) {
            return s_log();
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
        return s_log();
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
        return false;
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

    if (empty($arr)) {
        return false;
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

        || s_bad_id($v1['id'], $pid)
    ) {
        //没有指定主键，更新失败
        return s_log("no primary key.");
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
        if (!isset($v1[$key])
            || $v1[$key] != $v2[$key]
        ) {
            $values[] = "`{$key}`=" . ( is_string($value) ? '"' . s_safe_value($value) . '"' : $value );
        }
    }

    if (empty($values)) {
        //不需要修改
        return false;
    }

    return s_db_exec("update `{$table}` set " . implode(", ", $values) . " where `id`={$pid}");
}


function _s_db_delete($table, $v1) {
    if (s_bad_string($table, $table, true)
        //是数组取主键值
        || !( $v1 = ( is_array($v1) && isset($v1["id"]) ) ? intval($v1["id"]) : $v1 )
        || s_bad_id($v1)
    ) {
        return false;
    }


    if (defined("APP_DB_PREFIX")) {
        //替换表名:"%s_user:update" => "201204disney_user:update"
        $table = sprintf($table, APP_DB_PREFIX, true);
    }
    $sql  = "update `{$table}` set `status`=-1 where `id`= {$v1}";

    return s_db_exec($sql);
}
