<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.action.php
//	获取action的相关函数
//
//	s_action_time()
//	    返回服务器请求的时间
//  
//	s_action_json($data)
//	    以json返回$data数据
//  
//	s_action_xml($data)
//	    以xml返回$data数据
//
//	s_action_ip()
//	    返回请求的ip地址
//
//
//
////////////////////////////////////////////////////////////////////////////////


function s_action_time() {
    return $_SERVER["REQUEST_TIME"];
}


function s_action_json($data) {
    echo json_encode($data);
}


function s_action_xml($data) {
    s_action_json($data);
}


function s_action_ip() {
    return "000.000.000.000";
}
