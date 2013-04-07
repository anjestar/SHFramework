<?php
////////////////////////////////////////////////////////////////////////////////
//
//  devinc.s3upload.php
//      上传文件至s3（video.vic.sina.com.cn）服务器
//
//
//  s_s3upload_content(&$content, $dir=false,
//          $type   ='text/unknown',
//          $domain ='',
//          $key    ='',
//          $secret ='')
//    将$content内容写入到s3服务器中
//    dir未指定，会自动创建一个目录
//  
//  s_s3upload_path(@filepath, $dir=false, $type='text/unknown')
//    将filepath对应的文件上传到s3服务器中
//    dir未指定，会自动创建一个目录
//
//  s_s3upload_image(@filepath, $dir=false)
//    将图片上传s3服务器
//    dir未指定，会自动创建一个目录
//  
//
////////////////////////////////////////////////////////////////////////////////

require("SinaService/SinaStorageService/SinaStorageService.php");

//上传内容到S3中
function s_s3_upload($path, &$content,
            //文件类型
            $type='image/unknown',
            //域（上传到某个域下）
            $domain='video.vic.sina.com.cn',
            //密钥
            $key='SINA00000000000SALES', $secret='tyYXmhVGXmvJJJiwfeoqTOqCdKV/haHqrnwK0Pjy') {

    if ($content === false
        || $path === false
        || !( $upload = SinaStorageService::getInstance($domain, $key, $secret) )
    ) {
        return false;
    }

    if ($path === 'autocreate') {
        //自动创建目录
        $path = '/shframework/' . rand(0, 32) . '/' . time();
    }

    $upload->setCURLOPTs(array(CURLOPT_VERBOSE => 1));
    //$upload->setQueryStrings(array("v" => 1));
    //$upload->setExpires(time() + $this->expires);

    //是否验证
    $upload->setAuth(true);
    $upload->uploadFile($path, $content, false, $type, $result, true);

    return json_encode($result);
}

function s_s3upload_content($source, $content, $type='image/unknown') {
    return s_s3_upload($source, $content);
}


function s_s3upload_path($source, $target, $type='image/unknown') {
    return s_s3_upload($source, @file_get_contents($target));
}
