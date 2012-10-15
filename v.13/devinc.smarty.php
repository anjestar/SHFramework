<?php
////////////////////////////////////////////////////////////////////////////////
// devinc.smarty.php
//	smarty渲染的函数
//
//	
//	s_smarty($tpl, $arg)
//	    将数据合并到模板中，供模板输出
//
//
////////////////////////////////////////////////////////////////////////////////


require_once(FRAMEWORK_DIR . '/../dev/smarty/Smarty.class.php');

function s_smarty_object() {
    //生成新的Smarty对象

    $smarty = new Smarty();
    $smarty->addPluginsDir(FRAMEWORK_DIR . '/dev/smarty/userplugins');

    $smarty_temp = '/tmp';

    $smarty->setCacheDir($smarty_temp);
    $smarty->setCompileDir($smarty_temp . '/templates_c');
    $smarty->setTemplateDir($smarty_temp . '/templates');

    return $smarty;
}


function s_smarty($tpl, &$assign=false) {
    if (!is_file($tpl)
        || !is_readable($tpl)
    ) {
        return s_err_arg('no suce file of ' . $tpl);
    }

    $smarty = s_smarty_object();

    if (!empty($assign)) {
        $smarty->assign($assign);
    }

    $smarty->display($tpl);
}



//返回渲染tpl后的字符串
function s_smarty_tpl($tpl) {
    if (!is_file($tpl)
        || !is_readable($tpl)
    ) {
        return s_err_arg('no suce file of ' . $tpl);
    }


    $smarty = s_smarty_object();

    try {
        return  $smarty->fetch($tpl);

    } catch (SmartyException $se) {

        return s_log($se->getMessage());
    }
}

