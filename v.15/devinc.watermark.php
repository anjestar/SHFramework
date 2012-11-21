<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.watermark.php
//	判断错误的函数，参数错误返回true，正确返回false
//
//  s_watermark(&$photo, &$watermark)
//	    在图片上生成水印
//  
//
//
////////////////////////////////////////////////////////////////////////////////


//返回以json格式的weibo数据，此处为做error_code检查
function &s_watermark(&$photo, &$watermark, $x=0, $y=0) {
    if (s_bad_gd()
        || s_bad_string($photo)
        || s_bad_string($watermark)
    ) {
        return s_err_arg();
    }

    //获取底板图片和水印图片
    if (false === ( $plate = @imagecreatefromjpeg($photo) )
        || false === ( $water = @imagecreatefrompng($watermark) )
    ) {
        return s_err_log('image error.');
    }


    //计算水平间隔
    //检查图片大小
    $p_w = imagesx($plate);
    $p_h = imagesy($plate);

    $w_w = imagesx($water);
    $w_h = imagesy($water);


    if ($p_w < $w_w
        || $p_h <$w_h
    ) {
        //消毁对象
        imagedestroy($plate);
        imagedestroy($water);

        return s_err_log('water height or width more than plate');
    }

    //TODO: 使用临时目录
    $time = s_action_time();
    $path = '/tmp/';
    $path .=  defined('APP_NAME') ? APP_NAME : date('Y-m-d', $time) . '_auto';

    if (!is_dir($path)
        && !mkdir($path, 0755, true)
    ) {
        return false;
    }

    $file = $path . '/' . $time . '_' . rand(1, 10000) . '.jpg';

    //合并图片
    if (false === imagecopy($plate, $water, $p_w - $w_w, $p_h - $w_h, 0, 0, $w_w, $w_h)
        || false === imagejpeg($plate, $file)
    ) {
        return s_err_log('unsuccess to {$file}.');
    }


    //消毁对象
    imagedestroy($plate);
    imagedestroy($water);

    //返回图片地址或者图片数据
    return $file;
}
