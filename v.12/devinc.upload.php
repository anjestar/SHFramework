<?php
////////////////////////////////////////////////////////////////////////////////
// devinc.upload.php
//	处理上传的文件
//
//	s_upload_image($name, $size)
//	    返回$_FILE[$name]中文件大小符合$size的图片（包括jpg, png, gif, bmp...），并且成功上传到持久存储的URL地址
//
//	s_upload_file($name, $size, $extension=false)
//	    返回$_FILE[$name]中文件大小符合$size的文件，并且成功上传到持久存储的URL地址
//
//  常用的文件类型:
//  图片：array(
//                'image/jpeg',
//                'image/pjpeg',
//                'image/gif',
//                'image/png',
//                'image/jpg',
//                'image/x-png',
//                'image/bmp',
//  )
//
////////////////////////////////////////////////////////////////////////////////


require_once("VFS/VFS/dpool_storage.php");


function s_upload_url($name, $size=2000, $types=false) {
    if (!( $file = s_upload_file($name) )) {
        return false;
    }

    //生成目录
    $dir    = SINA_UPLOAD_DIR . date('Y-m-d') . s_action_time() .  srand((double) microtime() * 1000000);
    //生成文件名
    $fname  = md5($dir . $file['name']) . substr($file['type'], strrpos('/', $file['type']));

	$vfs    = new VFS_dpool_storage();	
	$ret    = $vfs->write($dir, $fname, $file['tmp_name'], true);

    //检查是否正确
	if (is_a($ret, "PEAR_Error")) {			
	    return false;
    }

    return $dir . $fname;
}


function s_upload_file($name, $size=2000, $types=false) {
    if (!isset($_FILES[$name]['tmp_name'])
        || $_FILES[$name]['error'] > 0
        || !is_uploaded_file($_FILES[$name]['tmp_name'])
    ) {
        return false;
    }

    $file = $_FILES[$name];

    if ($file['size'] > $size) {
        return false;
    }

        //不在数组中
    if (is_array($types)
        && !in_array($file['type'], $types)
    ) {
        return false;

        //是否这个类型
    }  else if ($types !== $file['type']) {
        return false;
    }

    return $file;
}
