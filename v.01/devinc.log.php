<?php
////////////////////////////////////////////////////////////////////////////////
// devinc.log.php
//	日志文件的输出格式为
//	2011-01-01 [duanyong:127.0.0.1] ACTION START: /diary/add
//	    01:01:01.290087 [warning] warning arg [/drevinc.mysql.php:15]
//	    01:01:01.290086 [notice] warning sql [/devinc.mysql.php:14]
//	    01:01:01.290090 [error] argument is null [/devinc.mysql.php:15]
//
///	2011-01-01 [fengyu:127.0.0.1] ACTION START: /user/add
//	    01:01:02.290087 [warning] warning arg [/drevinc.mysql.php:15]
//	    01:01:02.290086 [notice] warning sql [/devinc.mysql.php:14]
//	    01:01:02.290090 [error] argument is null [/devinc.mysql.php:15]
//
//
//	s_log($log)
//	    输出日志
//
//	s_err_arg($log)
//
//	s_err_sql($log)
//
//	s_err_action($log)
//
//	s_error($log)
//	    中断执行
//
//	s_log_on()
//	    开启日志输出
//
//	s_log_off()
//	    关闭日志输出
//
//
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////


//$log_file       = "/var/log/nginx/php.log";     //日志文件
$log_on	        = true;                         //是否开启日志输出（默认开启）
$log_is_cli     = php_sapi_name() === "cli";    //是否在cli模式下运行


//所有的错误信息都将提交到此，待cgi执行完成时再处理错误信息
static $_traces = array();


//
////////////////////////////////////////////////////////////////////////////////


error_reporting(E_ALL);

//ini_set('display_errors', 'Off');


//开启日志输出
function s_log_on() {
    global $log_on;

    return $log_on = true;
}


//关闭日志输出
function s_log_off() {
    global $log_on;

    return !( $log_on = false );
}

//E_USER_NOTICE	- 默认。用户生成的 run-time 通知。脚本发现了可能的错误，也有可能在脚本运行正常时发生。
function s_log($log=false, $level=E_USER_NOTICE) {
    global $_traces;

    $trace          = debug_backtrace();
    $trace['msg']   = $log;

    $_traces[]      = $trace;

    return false;
}


function s_err_action($log="warning action") {
    return s_log('ACTION:' . $log);
}


function s_err_arg($log="warning arg") {
    return s_log('ARG:' . $log);
}

function s_err_sql($log="warning sql") {
    return s_log('SQL:' . $log);
}

function s_error($log="error, stop it!") {
    s_log('ERR:' . $log);

    //中断脚本执行，返回PHP E_ERROR错误
    exit(E_ERROR);
}

//将各种debug_traceback转换成字符串
function s_log_trace(&$trace, &$msg=false) {
    if (!is_array($trace)) {
        return false;
    }

    $msg  = $msg ? ' =>' . $msg : '';

    $args = array();
    $list = $trace['args'];

    foreach ($list as &$arg) {
        $args[] = str_replace("\n", '', print_r($arg, true));

        unset($arg);
    }

    //将debug_traceback转换成字符串
    return sprintf("[%s][LOG]:%s [%d]:%s(%s) %s", date('m-d H:i:s', s_action_time()), $trace['file'], $trace['line'], $trace['function'], implode(', ', $args), $msg);
}


//自定义日志输出函数
function s_log_printf() {
    global $_traces;

    $log = "";

    foreach ($_traces as &$trace){
        $msg = isset($trace['msg']) ? $trace['msg'] : false;

        for ($i = 2, $len = count($trace); $i < $len; ++ $i) {
            //前两次调用属于s_err_arg => s_log，无用
            $log .= "\n" . s_log_trace($trace[$i], $msg);
        }

        unset($trace);
    }


    if ($log) {
        //输出到页面中
        print_r($log);
    }
}


//系统调用
function s_error_handler(&$no=0, &$log=false, &$file=false, &$line=0, &$context=false) {
    echo "system:{$no}";
    return s_log('ERR:' . $log);
}

//TODO: 系统调用并未发生
set_error_handler('s_error_handler');
register_shutdown_function('s_log_printf');

