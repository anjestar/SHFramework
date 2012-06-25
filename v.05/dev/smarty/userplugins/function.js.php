<?php
//将file对应的js文件从/js/*.js外联进来
//  {js name="layout, login"}
//
function smarty_function_js($params, $template) {
    if (empty($params["name"])) {
        //参数错误，返回空字符串

        return "";
    }

    $jses   = array();
    $files  = explode(",", str_replace(" ", "", $params["name"]));

    foreach ($files as &$js) {
        //测试文件是否可读或者存在
        if (is_readable(ROOT_DIR . '/js/' . $js . '.js')) {
            $jses[] = '<script type="text/javascript" src="/js/' . $js . '.js"></script>';
        }

        unset($js);
    }

    return implode("\r\n", $jses);
}
