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
function s_s3_upload(&$content,
            //文件类型
            $type='jpg',
            //上传地址
            $target=false,
            //域（上传到某个域下）
            $domain='video.vic.sina.com.cn',
            //密钥
            $key='SINA00000000000SALES', $secret='tyYXmhVGXmvJJJiwfeoqTOqCdKV/haHqrnwK0Pjy') {

    if ($content === false
        || !( $upload = SinaStorageService::getInstance($domain, $key, $secret) )
    ) {
        return false;
    }

    //选择类别
    if (!( $mime = s_s3upload_mime($type) )) {
        $mime = 'image/unknown';
    }


    if ($target === false) {
        //自动创建目录
        $path = 'shframework/' . date('Y') . '/' . rand(0, 364) . '/' . time() . rand(3, 100) . '.' . $type;
    }

    $upload->setAuth(true);

    //$upload->setExpires(time() + 2678400);
    //$upload->setQueryStrings(array("v" => 1));
    $upload->setCURLOPTs(array(CURLOPT_VERBOSE => 1));

    if (!( $upload->uploadFile($path, $content, strlen($content), $mime, $result, true) )) {
        return false;
    }

    $upload = SinaStorageService::getInstance($domain, $key, $secret, true);

    return $upload->getFileUrl($path, $result);
}


function s_s3upload_content($content, $type='jpg', $target=false) {
    return s_s3_upload($content, $type, $target);
}


function s_s3upload_path($source, $type='jpg', $target=false) {
    return s_s3_upload(@file_get_contents($source), $type, $target);
}


function s_s3upload_mime($type) {
    $mimes = array(
        'gif'       => 'image/gif',
        'jpg'       => 'image/jpeg',
        'jpeg'      => 'image/jpeg',
        'jpe'       => 'image/jpeg',
        'bmp'       => 'image/bmp',
        'png'       => 'image/png',
        'tif'       => 'image/tiff',
        'tiff'      => 'image/tiff',
        'pict'      => 'image/x-pict',
        'pic'       => 'image/x-pict',
        'pct'       => 'image/x-pict',
        'tif'       => 'image/tiff',
        'tiff'      => 'image/tiff',
        'psd'       => 'image/x-photoshop',
        'wbmp'      => 'image/vnd.wap.wbmp',

        'css'       => 'text/css',
        'htm'       => 'text/html',
        'html'      => 'text/html',
        'txt'       => 'text/plain',
        'xml'       => 'text/xml',
        'wml'       => 'text/wml',
        'uu'        => 'text/x-uuencode',
        'uue'       => 'text/x-uuencode',

        'mid'       => 'audio/midi',
        'wav'       => 'audio/wav',
        'mp3'       => 'audio/mpeg',
        'mp2'       => 'audio/mpeg',

        'avi'       => 'video/x-msvideo',
        'mpeg'      => 'video/mpeg',
        'mpg'       => 'video/mpeg',
        'qt'        => 'video/quicktime',
        'mov'       => 'video/quicktime',

        'z'         => 'application/x-compress',
        'pdf'       => 'application/pdf',
        'swf'       => 'application/x-shockwave-flash',
        'js'        => 'application/x-javascrīpt',
        'ps'        => 'application/postscrīpt',
        'eps'       => 'application/postscrīpt',
        'ai'        => 'application/postscrīpt',
        'wmf'       => 'application/x-msmetafile',
        'lha'       => 'application/x-lha',
        'lzh'       => 'application/x-lha',
        'gtar'      => 'application/x-gtar',
        'gz'        => 'application/x-gzip',
        'gzip'      => 'application/x-gzip',
        'tgz'       => 'application/x-gzip',
        'tar'       => 'application/x-tar',
        'bz2'       => 'application/bzip2',
        'zip'       => 'application/zip',
        'arj'       => 'application/x-arj',
        'rar'       => 'application/x-rar-compressed',
        'hqx'       => 'application/mac-binhex40',
        'sit'       => 'application/x-stuffit',
        'bin'       => 'application/x-macbinary',
        'latex'     => 'application/x-latex',
        'ltx'       => 'application/x-latex',
        'tcl'       => 'application/x-tcl',
        'pgp'       => 'application/pgp',
        'asc'       => 'application/pgp',
        'exe'       => 'application/x-msdownload',
        'doc'       => 'application/msword',
        'rtf'       => 'application/rtf',
        'xls'       => 'application/vnd.ms-excel',
        'ppt'       => 'application/vnd.ms-powerpoint',
        'mdb'       => 'application/x-msaccess',
        'wri'       => 'application/x-mswrite',
        'flv'       => 'flv-application/octet-stream'
    );

    return $mimes[$type];
}
