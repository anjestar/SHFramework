<?php
//将name对应的css文件从/css/*.css外联进来
//  {css name="layout, index"}
//
function smarty_function_css($params, $template) {
    if (empty($params["name"])) {
        //参数错误，返回空字符串
        return "";
    }

    //外联css
    $csses  = array();
    $files  = explode(",", str_replace(" ", "", $params["name"]));

    foreach ($files as &$css) {
        if (is_readable($_SERVER['DOCUMENT_ROOT'] . '/' . APP_NAME .  '/css/' . $css . '.css')) {
            //文件可读
            $csses[] = '<link rel="stylesheet" type="text/css" href="/css/' . $css  .'.css" media="screen"></link>';
        }

        unset($css);
    }

    return implode("\r\n", $csses);
}

