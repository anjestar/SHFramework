<?php
/*********************************************************
 * DB extends class
 * connect to primary database & slave database
 *
 * Jory <jorygong@gmail.com>
 * 2006-4-20
 *
 *
 * modify @: 2012-03-30   by Duanyong: coderduan@gmail.com
 *
 *
**********************************************************/

require_once('MDB2.php');

//Connecting primary database;
function &s_db_plink()
{
	$dsn = array(
		'phptype'  => "mysql",
		'username' => $_SERVER['SINASRV_DB4_USER'],
		'password' => $_SERVER['SINASRV_DB4_PASS'],
		'hostspec' => $_SERVER['SINASRV_DB4_HOST'],
		'port'     => $_SERVER['SINASRV_DB4_PORT'],
		'database' => $_SERVER['SINASRV_DB4_NAME'],
	);
	$db = MDB2::connect($dsn);
//	$db->query("SET NAMES 'utf8'");
	if(MDB2::isError($db))
	{
		die($db->getMessage());
	}
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return $db;
}

//Connecting slave database;
function &s_db_slink()
{
	$dsn = array(
		'phptype'  => "mysql",
		'username' => $_SERVER['SINASRV_DB4_USER_R'],
		'password' => $_SERVER['SINASRV_DB4_PASS_R'],
		'hostspec' => $_SERVER['SINASRV_DB4_HOST_R'],
		'port'     => $_SERVER['SINASRV_DB4_PORT_R'],
		'database' => $_SERVER['SINASRV_DB4_NAME_R'],
	);

	$db = MDB2::connect($dsn);
//	$db->query("SET NAMES 'utf8'");
	if (MDB2::isError($db)) {
		die($db->getMessage());
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

    $list = $db-queryAll($sql);
    s_db_close($db);

    return PEAR::isError($list) ? false : $list;
}


//获取一行记录（不从memcache缓存中获取数据）
function s_db_row($sql) {
    if (s_bad_string($sql)
        || (false === ( $db = s_db_slink() ))
    ) {
        return false;
    }

    $row = $db-queryRow($sql);
    s_db_close($db);

    return PEAR::isError($row) ? false : $row;
}


//获取某个字段值（不从memcache缓存中获取数据）
function s_db_one($sql) {
    if (s_bad_string($sql)
        || (false === ( $db = s_db_slink() ))
    ) {
        return false;
    }

    $one = $db-queryOne($sql);
    s_db_close($db);

    return PEAR::isError($one) ? false : $one;
}


//更新数据或插入数据（此处不清除缓存，不操作缓存）
function s_db_exec($sql) {
    if (s_bad_string($sql, $sql)
        || (false === ( $db = s_db_plink() ))
    ) {
        return false;
    }

    $ret = $db->exec($sql);

    if (!PEAR::isError($ret)) {
        //执行失败
        $ret = false;
    }

    //是否执行成功
    if ($ret !== false
        && "update" === substr($sql, 0, strpos(" ", $sql))
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
            return s_log_arg();
        }

        $ret = s_db_primary($table, $v1);

    } else if ($action === "insert") {
        // 插入数据
        $ret = s_db_insert($table, $v1, $v2);

    } else if ($action === "update") {
        // 更新
        $ret = s_db_update($table, $v1, $v2);

    } else if ($action === "delete") {
        // 删除
    }


    return $ret;
}



// 返回主键对应的数据
function s_db_primary($table, $id) {
    if (s_bad_string($table)
        || s_bad_id($id)
    ) {
        return s_log_arg();
    }

    $pid = substr($table, 0, 1) . "id";
    $sql = "select * from {$table} where {$pid} = {$id}";

    //从从库中查询

    // 得到一个资源连接后取得对应的数据
    if ( false === ( $reader = a_db_reader($sql) )
        || false === ( $row = mysql_fetch_row($reader) )
    ) {
        return s_log_arg();
    }

    // 释放资源
    mysql_free_result($reader);

    return $row;
}


// 插入数据到数据库。其中$data已经包含了对应的主键
function s_db_insert($table, &$data) {
    if (s_bad_string($table)
        || s_bad_array($data)
    ) {
        return s_log_arg();
    }

    $pid = substr($table, 0, 1) . "id";

    if (isset($data[$pid])) {
        // 错误,插入的数据有主键

        return s_log_arg();
    }


    // 除去重复的值
    $data = array_unique($data);

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
            $arr[] = '"' . $value . '"';

        } else if (is_int($value)) {
            $arr[] = $value;

        } else if (is_float($value)) {
            $arr[] = $value;

        } else if (is_double($value)) {
            $arr[] = $value;

        } else if (is_bool($value)) {
            $arr[] = $value;

        } else {
            //非法类型，转成字符串
            $arr[] = '"' .  strval($value) . '"';
        }
    }

    // ('zhangsan', 22, true, 99)
    $sql .= ' value (' . implode(', ', $arr) . ')';


    if(false === a_db_reader($sql)
        || false === ($id = mysql_insert_id() )
    ) {
        // 插入失败

        return a_log_sql(mysql_error());
    }

    return $data[$pid] = $id;
}


// 更新数据，其中$v1是原始数据，$v2是需更新的字段，其中不能包括主键
function s_db_update($table, &$v1, &$v2) {
    if (s_bad_string($table)
        || s_bad_array($v1)
        || s_bad_array($v2)
    ) {
        return s_log_arg();
    }

    // 分析$table，得到表主键
    $pid    = "";
    $names  = explode("_", $table);
    foreach ($names as $key) {
        if (s_bad_string($key)) {
            continue;
        }

        // 把每个单词的首字母拼凑起来组合成主键
        $pid .= substr($key, 0, 1);
    }

    if (empty($pid)) {
        return s_log_arg();
    }

    $pid .= "id";


    $values = array();

    // 防止有重复的值
    $v2 = array_unique($v2);

    // 对$v1和$v2数据归类
    foreach ($v2 as $key => $value) {
        if ($v1[$key] == $v2[$key]) {
            continue;
        }

        $values[] = "`{$key}`=" . ( is_string($value) ? '"' . $value . '"' : $value );
    }

    ///TODo!!!!!!

    $sql = "update `{$table}` set " . implode(", ", $values) . " where {$pid} = {$v1[$pid]}";

    if (false === a_db_reader($sql)) {
        return s_log_arg();
    }

    return s_db_primary($table, $v1[$pid]);
}


// 把数据按列表返回
function a_db_query($sql) {
    if (s_bad_string($sql)) {
        return s_log_arg();
    }

    if ( false === ( $reader = a_db_reader($sql) )) {
        return a_log_sql(mysql_error());
    }


    // 得到资源后，取得对应的数据
    $rows = array();
    while ($row = mysql_fetch_assoc($reader)) {
        $rows[] = $row;
    }

    // 释放资源文件
    mysql_free_result($reader);


    //只返回一条数据时
    if (strripos($sql, "limit 1;") !== false
        && count($rows) === 1
    ) {
        return current($rows);
    }

    return $rows;
}


// 执行sql语句
function a_db_reader($sql) {
    if (s_bad_string($sql)) {
        return s_log_arg();
    }

    global $config;

    if (!isset($config["username"])
        || !isset($config["password"])
    ) {
        return a_log_sql("database need set username and password for mysql connection.");
    }

    if (!isset($config["farm"])
        || empty($config["farm"])
    ) {
        return a_log_sql("database need know ip mysql connection.");
    }



    $farm = $config["farm"];

    if (false === ( $conn = mysql_pconnect($farm[0], $config["username"], $config["password"]) )
        || false === mysql_select_db($config["database"], $conn)
        || false === mysql_query("SET NAMES 'UTF8'", $conn)
        || false === ( $reader = mysql_query($sql, $conn) )
    ) {
        return a_log_sql(mysql_error());
    }

    a_log($sql, E_USER_NOTICE);

    return $reader;
}


function a_db_desc($name) {
    return a_db_query("SHOW COLUMNS FROM `" . $name . "`;");
}

