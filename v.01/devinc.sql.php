<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.bad.php
//	判断错误的函数，参数错误返回true，正确返回false
//
//	s_bad_id($id)
//	    判断数字是否正确（大于0）
//  
//	s_bad_0id($id)
//	    判断数字是否正确（等于0也可以）
//  
//	s_bad_string($string)
//	    判断字符串是否正确
//
//	s_bad_array($string, &$var)
//	    判断数组否是正确，如果正确赋值给$var变量
//
//	s_bad_email($email, $var)
//	    判断邮箱地址是否正确
//
//
//
////////////////////////////////////////////////////////////////////////////////



function s_sql_table($sql, $select, $update, $insert) {
    if (s_bad_string($sql, $sql)) {
        return false;
    }

    $pos = false;
    if ($select !== -1) {
        //select语句
        $pos = strpos($sql, " from ") + 6;

    } else if ($update !== -1) {
        //update语句
        $pos = 6;

    } else if ($insert !== -1) {
        //insert into table set field1=value1, filed2=value2, field3=value3;
        //insert into table () values ();
        $pos = 11;
    }

    return substr($sql, $pos, strpos($sql, " ", $pos + 1) - $pos);
}


function s_sql_where($sql, $order_pos=false, $group_pos=false, $limit_pos=false) {
    if (s_bad_string($sql)
        || ( $pos = strpos($sql, " where ") ) === -1
    ) {
        return false;
    }

    if ($order_pos) {
        //有order
        $sql = substr($sql, $pos, $order_pos);

    } else if ($group_pos) {
        //有group by
        $sql = substr($sql, $pos, $group_pos);

    } else if ($limit_pos) {
        //有limit
        $sql = substr($sql, $pos, $limit_pos);
    }

    $sql = str_replace(array("'", '"', '`'), '', $sql);

    return explode(",", $sql);
}


function s_sql_filed($sql, $from_pos, $select_pos) {
    if (s_bad_string($sql)
        || s_bad_id($from_pos)
    ) {
        return false;
    }

    if (!$select) {
        $sql        = trim($sql);
        $select_pos = 0;
    }

    $sql = substr($sql, $select_pos + 5, $from_pos);
    $sql = str_replace(array("`", " "), "", $sql);

    return explode(",", $sql);
}


function s_sql_limit($sql, $limit_pos) {
    if (s_bad_string($sql)
        || s_bad_id($limit_pos)
    ) {
        return false;
    }


    $sql = substr($sql, $limit_pos + 5);
    $sql = str_replace(" ", "", $sql);
    $sql = explode(",", $sql);

    return count($sql) === 1 ? array(0, $sql[0]) : $sql;
}


function s_sql_desc($sql) {
    if (s_bad_string($sql, $sql)) {
        return false;
    }

    $from   = strpos($sql, "from ");
    $where  = strpos($sql, "where ");
    $group  = strpos($sql, "group ");
    $order  = strpos($sql, "order ");
    $limit  = strpos($sql, "limit ");
    $select = strpos($sql, "select ");
    $update = strpos($sql, "update ");
    $insert = strpos($sql, "insert ");
    $type   = substr($sql, strpos($sql, " "));


    return array(
        "type"  => $type,
        "table" => s_sql_table($sql, $select, $update, $insert),
        "fields"=> s_sql_filed($sql, $from, $select),
        "where" => s_sql_where($sql, $order, $group, $limit),
        "limit" => s_sql_limit($sql, $limit),
    );
}
