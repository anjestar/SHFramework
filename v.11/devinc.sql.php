<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.sql.php
//	分析sql语言中关键字及对应的值
//
//	s_sql_select($sql, $from, $select=0)
//	    以数组返回select后的查询关键字
//  
//	s_sql_table($sql, $select, $update, $insert, $from)
//	    返回sql语句中的表名
//  
//	s_sql_where($sql, $where, $order=false, $group=false, $limit=false)
//	    以数组返回where后的条件关键字
//
//	s_sql_group($sql, $group=0, $order=false, $limit=false)
//	    以数组返回group后的分组关键字
//
//	s_sql_order($sql, $order, $group=false, $limit=false)
//	    以数组返回order后的排序关键字
//
//	s_sql_limit($sql, $limit) {
//	    以数组返回limit后的列表范围关键字
//
//
//
////////////////////////////////////////////////////////////////////////////////


//取得sql语句中的表名
function s_sql_table($sql, $select, $update, $insert, $from) {
    if (s_bad_string($sql, $sql)) {
        return false;
    }

    $pos = false;
    if ($select !== false
        && !s_bad_id($from)
    ) {
        //select语句
        $pos = $from + 5;

    } else if ($update !== false) {
        //update语句
        $pos = 6;

    } else if ($insert !== false) {
        //insert into table set field1=value1, filed2=value2, field3=value3;
        //insert into table () values ();
        $pos = 11;
    }

    return trim(substr($sql, $pos, strpos($sql, " ", $pos + 1) - $pos));
}



//取得where后所有的条件语句(where a=1 and b=1 and c=1 order by xx group by yy limit 10)
function s_sql_where($sql, $where, $order=false, $group=false, $limit=false) {
    if (s_bad_string($sql)
        || s_bad_id($where)
    ) {
        return false;
    }

    $pos += $where + 6;

    if ($order) {
        //有order
        $sql = substr($sql, $pos, $order - $pos);

    } else if ($group) {
        //有group by
        $sql = substr($sql, $pos, $group - $pos);

    } else if ($limit) {
        //有limit
        $sql = substr($sql, $pos, $limit - $pos);

    } else {
        //没有order, group, limit，直接取到sql语句末尾
        $sql = substr($sql, $pos);
    }

    $sql = str_replace(array("'", '"', '`', " ", "and"), array("", "", "", "", ","), $sql);
    $arr = explode(",", $sql);

    $ret = array();

    foreach ($arr as $v) {
        $v = explode("=", $v);

        $ret[$v[0]] = isset($v[1]) ? $v[1] : "";
    }

    return $ret;
}



//取得查询字段(select id, age, name, class from student)
function s_sql_select($sql, $from, $select=0) {
    if (s_bad_string($sql, $sql)
        || s_bad_0id($select)
        || s_bad_id($from)
    ) {
        return false;
    }

    $sql = substr($sql, $select += 6, $from - $select);
    $sql = str_replace(array("`", " "), "", $sql);
    $sql = explode(",", $sql);

    return count($sql) === 1 && $sql[0] === "*" ? "*" : $sql;
}


//TODO:
function s_sql_group($sql, $group=0, $order=false, $limit=false) {
    if (s_bad_string($sql, $sql)) {
        return false;
    }

    return array();
}


function s_sql_order($sql, $order, $group=false, $limit=false) {
    if (s_bad_string($sql)) {
        return false;
    }

    $pos1 = false;

    if ($group) {
        $pos1 = $group;

    } else if ($limit) {
        $pos1 = $limit;

    } else {
        $pos1 = strlen($sql);
    }

    $sql = substr($sql, $pos += 9, $pos1 - $pos);
    $sql = str_replace(array("'", '"', '`'), "", $sql);
    $arr = explode(",", $sql);

    $ret = array();

    foreach ($arr as $v) {
        $v = explode(" ", trim($v));

        $ret[$v[0]] = !isset($v[1]) || strtolower($v[1]) !== "desc" ? "asc" : "desc";
    }


    return $ret;
}



function s_sql_limit($sql, $limit) {
    if (s_bad_string($sql)
        || s_bad_id($limit)
    ) {
        return false;
    }


    $sql = substr($sql, $limit + 5);
    $sql = str_replace(" ", "", $sql);
    $sql = explode(",", $sql);

    return count($sql) === 1 ? array("0", $sql[0]) : $sql;
}


function s_sql_desc($sql) {
    if (s_bad_string($sql, $sql)) {
        return false;
    }

    $select = strpos($sql, "select ");
    $insert = strpos($sql, "insert ");
    $update = strpos($sql, "update ");
    $from   = strpos($sql, "from ");
    $where  = strpos($sql, "where ");
    $group  = strpos($sql, "group ");
    $order  = strpos($sql, "order ");
    $limit  = strpos($sql, "limit ");
    $type   = substr($sql, 0, strpos($sql, " "));

    //echo " from: {$from} \t where: {$where} \t group: {$group} \t order: {$order} \t limit: {$limit} \t type: {$type} ";


    return array(
        "sql"   => $sql,
        "type"  => $type,
        "table" => s_sql_table($sql, $select, $update, $insert, $from),
        "select"=> s_sql_select($sql, $from, $select),
        "where" => s_sql_where($sql, $where, $order, $group, $limit),
        "group" => s_sql_group($sql, $group, $order, $limit),
        "limit" => s_sql_limit($sql, $limit),
    );
}
