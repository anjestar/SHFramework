<?php 

/*
* How to use?
*
* $w = new Weibo( 'APP Key' );
* $w->setUser( 'username' , 'password' );
* print_r($w->public_timeline());
*
* send image
* $w->upload( 'image test' , file_get_contents('http://tp4.sinaimg.cn/1088413295/180/1253254424') );
*
*/


class Weibo
{
	function __construct($akey, $skey)
	{
		$this->akey = $akey;
		$this->skey = $skey;
		//$this->akey = 12051063;
		//$this->skey = "4ba35c54132b335dd9e2dc475e76ca93";
		$this->base = 'http://api.t.sina.com.cn/';
		$this->curl = curl_init();
		curl_setopt( $this->curl , CURLOPT_RETURNTRANSFER, true);

		$this->postInit();
	
		//使用用户名、密码
		$cookie=$this->join_cookie();
		curl_setopt($this->curl, CURLOPT_COOKIE , $cookie);
	}

	function postInit()
	{
		$this->postdata = array('source=' . $this->akey);

	}

	function setUser( $name , $pass )
	{
		$this->user['oname'] = $name;
		$this->user['opass'] = $pass;

		$this->user['name'] = $name;
		$this->user['pass']  = $pass;
		//curl_setopt( $this->curl , CURLOPT_USERPWD , "$name:$pass" );
	}

	//获取最新的公共微博消息
	function public_timeline()
	{
		return $this->call_method( 'statuses' , 'public_timeline' );
	}

	//获取当前登录用户及其所关注用户的最新微博消息
	function friends_timeline()
	{
		return $this->call_method( 'statuses' , 'friends_timeline' );
	}

	//获取当前登录用户发布的微博消息列表 微博名
	function user_timeline($name)
	{
		return $this->call_method( 'statuses' , 'user_timeline' , '?screen_name=' . urlencode( $name ) );
	}
	
	//获取当前登录用户发布的微博消息列表 uid
	function user_timeline_id($uid)
	{
		return $this->call_method( 'statuses' , 'user_timeline' , '?user_id=' . $uid );
	}

	//获取@当前用户的微博列表 
	function mentions($count=10,$page=1)
	{
		return $this->call_method( 'statuses' , 'mentions' , '?count=' . $count . '&page=' , $page  );
	}

	//获取当前用户发送及收到的评论列表 
	function comments_timeline( $count = 10 , $page = 1 )
	{
		return $this->call_method( 'statuses' , 'comments_timeline' , '?count=' . $count . '&page=' , $page  );
	}

	//获取当前用户发出的评论  
	function comments_by_me( $count = 10 , $page = 1 )
	{
		return $this->call_method( 'statuses' , 'comments_by_me' , '?count=' . $count . '&page=' , $page  );
	}

	//根据微博消息ID返回某条微博消息的评论列表 
	function comments( $tid , $count = 10 , $page = 1 )
	{
		return $this->call_method( 'statuses' , 'comments' , '?id=' . $tid . '&count=' . $count . '&page=' , $page  );
	}

	//批量获取一组微博的评论数及转发数 
	function counts( $tids )
	{
		return $this->call_method('statuses','counts','?ids='.$tids);
	}

	//根据ID获取单条微博信息内容
	function show( $tid )
	{
		return $this->call_method( 'statuses' , 'show/' . $tid  );
	}

	//删除一条微博信息 
	function destroy( $tid )
	{

		//curl_setopt( $this->curl , CURLOPT_CUSTOMREQUEST, "DELETE");
		return $this->call_method( 'statuses' , 'destroy/' . $tid  );
	}

	//转发一条微博信息
	function repost( $tid , $status )
	{
		$this->postdata[] = 'id=' . $tid;
		$this->postdata[] = 'status=' . urlencode($status);
		return $this->call_method( 'statuses' , 'repost'  );
	}

	//发布一条微博信息
	function update( $status )
	{
		$this->postdata[] = 'status=' . urlencode($status);
		return $this->call_method( 'statuses' , 'update'  );
	}
	
	
	//关注列表
	function friends_list($uid)
	{
		return $this->call_method( 'friends' , 'ids' , '?user_id='.$uid   );
	}
	
	//粉丝id列表
	function followers_list($uid)
	{
		return $this->call_method( 'followers' , 'ids' , '?user_id='.$uid   );
	}
	
	//粉丝信息列表
	function followers_list2($uid,$count,$cursor)
	{
		return $this->call_method( 'statuses','followers','?user_id='.$uid."&count=".$count."&cursor=".$cursor);
	}
	
	//关注
	function Friendships_create($uid)
	{
		return $this->call_method( 'friendships' , 'create' , '?user_id='.$uid   );
	}
	
	//取消关注
	function Friendships_destroy($uid)
	{
		return $this->call_method( 'friendships' , 'destroy' , '?user_id='.$uid  );
	}

	//关注关系
	function Friendships_exists($uida,$uidb)
	{
		return $this->call_method( 'friendships' , 'exists' , '?user_a='.$uida."&user_b=".$uidb );
	}
	
	//上传图片并发布一条微博信息 
	function upload( $status , $file )
	{

		$boundary = uniqid('------------------');
		$MPboundary = '--'.$boundary;
		$endMPboundary = $MPboundary. '--';

		$multipartbody .= $MPboundary . "\r\n";
		$multipartbody .= 'Content-Disposition: form-data; name="pic"; filename="wiki.jpg"'. "\r\n";
		$multipartbody .= 'Content-Type: image/jpg'. "\r\n\r\n";
		$multipartbody .= $file. "\r\n";

		$k = "source";
		// 这里改成 appkey
		$v = $this->akey;
		$multipartbody .= $MPboundary . "\r\n";
		$multipartbody.='content-disposition: form-data; name="'.$k."\"\r\n\r\n";
		$multipartbody.=$v."\r\n";

		$k = "status";
		$v = $status;
		$multipartbody .= $MPboundary . "\r\n";
		$multipartbody.='content-disposition: form-data; name="'.$k."\"\r\n\r\n";
		$multipartbody.=$v."\r\n";
		$multipartbody .= "\r\n". $endMPboundary;

		curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt( $this->curl , CURLOPT_POST, 1 );
		curl_setopt( $this->curl , CURLOPT_POSTFIELDS , $multipartbody );
		$url = 'http://api.t.sina.com.cn/statuses/upload.json' ;

		$header_array = array("Content-Type: multipart/form-data; boundary=$boundary" , "Expect: ");

		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header_array );
		curl_setopt($this->curl, CURLOPT_URL, $url );
		curl_setopt($this->curl, CURLOPT_HEADER , true );
		curl_setopt($this->curl, CURLINFO_HEADER_OUT , true );

		$info = curl_exec( $this->curl );

		//print_r( curl_getinfo( $this->curl ) );
		$temp=explode('{"created_at":',$info);
		return json_decode('{"created_at":'.$temp[1],true);
	
		//return json_decode( $info , true);
		// =================================================
		//return $this->call_method( 'statuses' , 'upload'  );
	}
	
	
	//修改头像
	function update_profile_image($file )
	{

		$boundary = uniqid('------------------');
		$MPboundary = '--'.$boundary;
		$endMPboundary = $MPboundary. '--';

		$multipartbody .= $MPboundary . "\r\n";
		$multipartbody .= 'Content-Disposition: form-data; name="image"; filename="wiki.jpg"'. "\r\n";
		$multipartbody .= 'Content-Type: image/jpg'. "\r\n\r\n";
		$multipartbody .= $file. "\r\n";

		$k = "source";
		// 这里改成 appkey
		$v = $this->akey;
		$multipartbody .= $MPboundary . "\r\n";
		$multipartbody.='content-disposition: form-data; name="'.$k."\"\r\n\r\n";
		$multipartbody.=$v."\r\n";
		$multipartbody.= "\r\n". $endMPboundary;


		curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt( $this->curl , CURLOPT_POST, 1 );
		curl_setopt( $this->curl , CURLOPT_POSTFIELDS , $multipartbody );
		$url = 'http://api.t.sina.com.cn/account/update_profile_image.json' ;

		$header_array = array("Content-Type: multipart/form-data; boundary=$boundary" , "Expect: ");

		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header_array );
		curl_setopt($this->curl, CURLOPT_URL, $url );
		curl_setopt($this->curl, CURLOPT_HEADER , true );
		curl_setopt($this->curl, CURLINFO_HEADER_OUT , true );

		$info = curl_exec( $this->curl );

		//print_r( curl_getinfo( $this->curl ) );
		$temp=explode('{"id":',$info);
		return json_decode('{"id":'.$temp[1],true);
		//return json_decode( $info , true);
		// =================================================
		//return $this->call_method( 'statuses' , 'upload'  );
	}

	//对一条微博信息进行评论 
	function send_comment( $tid , $comment , $cid = '' )
	{
		$this->postdata[] = 'id=' . $tid;
		$this->postdata[] = 'comment=' . urlencode($comment);
		if( intval($cid) > 0 ) $this->postdata[] = 'cid=' . $cid;
		return $this->call_method( 'statuses' , 'comment'  );
	}

	//对一条微博评论进行回复评论
	function reply( $tid , $reply , $cid  )
	{
		$this->postdata[] = 'id=' . $tid;
		$this->postdata[] = 'comment=' . urlencode($reply);
		if( intval($cid) > 0 ) $this->postdata[] = 'cid=' . $cid;
		return $this->call_method( 'statuses' , 'comment'  );
	}

	//删除当前用户的微博评论信息 
	function remove_comment( $cid )
	{
		return $this->call_method( 'statuses' , 'comment_destroy/'.$cid  );
	}

	// add favorites supports

	//获取当前用户的收藏列表 
	function get_favorites( $page = false )
	{
		return $this->call_method( '' , 'favorites' , '?page=' . $page  );
	}

	//添加收藏
	function add_to_favorites( $sid )
	{
		$this->postdata[] = 'id=' . $sid;
		return $this->call_method( 'favorites' , 'create'   );
	}

	//删除当前用户收藏的微博信息 
	function remove_from_favorites( $sid )
	{
		$this->postdata[] = 'id=' . $sid;
		return $this->call_method( 'favorites' , 'destroy'   );
	}

	// add account supports
	//验证当前用户身份是否合法 
	function verify_credentials()
	{
		return $this->call_method( 'account' , 'verify_credentials' );
	}

	//获取微博用户信息 
	function get_user_for_uid($uid)
	{
		return $this->call_method( 'users' , 'show', '?user_id='.$uid );
	}

	//获取微博用户信息 
	function get_user_for_name($name)
	{
		return $this->call_method( 'users' , 'show', '?screen_name='.$name );
	}
	
	//通过短连接获取播放源代码
	function get_url_code($short_url)
	{
		return $this->call_method( 'widget' , 'show', '?short_url='.$short_url."&lang=zh&jsonp=test&vers=123" );
	}
	
	//表情
	function get_emotions()
	{
		return $this->call_method2( 'emotions');
	}
	
	//搜索
	function get_search($search,$page=1,$rpp=20,$where='')
	{
		if($where)
		{
			$and=$where.'&rpp='.$rpp;
		}
		else 
		{
			$and='?q='.$search.'&rpp='.$rpp.'&page='.$page;
		}
		return $this->call_method2( 'search',$and);
	}
	
	//搜索当前用户的@搜索列表 q 	true 	string 	搜索的关键字。必须进行URL_encoding。UTF-8编码
	//count 	false 	int 	每页返回结果数。默认10
	//type 	true 	int 	1代表粉丝，0代表关注人。另外， 粉丝最多返回1000个，关注人最多2000个
	//range 	false 	int 	0代表只查关注人，1代表只搜索当前用户对关注人的备注，2表示都查. 默认为2. 
	function get_at_user_for_search($name,$type=0)
	{
		return $this->call_method( 'search/suggestions' , 'at_users', '?q='.$name."&type=".$type);
	}
	
	//搜索话题
	function get_search_tags($search)
	{
		return $this->call_method( 'trends' , 'statuses', '?trend_name='.$search );
	}


	function call_method( $method , $action , $args = '' )
	{

		curl_setopt( $this->curl , CURLOPT_POSTFIELDS , join( '&' , $this->postdata ) );

		$url = $this->base . $method . '/' . $action . '.json' . $args ;

		curl_setopt($this->curl , CURLOPT_URL , $url );

		$ret = curl_exec( $this->curl );
		// clean post data
		$this->postInit();


		return json_decode( $ret , true);

	}
	
	function call_method2( $action , $args = '' )
	{

		curl_setopt( $this->curl , CURLOPT_POSTFIELDS , join( '&' , $this->postdata ) );

		$url = $this->base . $action . '.json' . $args ;

		curl_setopt($this->curl , CURLOPT_URL , $url );

		$ret = curl_exec( $this->curl );
		// clean post data
		$this->postInit();


		return json_decode( $ret , true);

	}

	function __destruct ()
	{
		//echo "#";echo $this->curl;echo "#";
		curl_close($this->curl);
	}
	
	//cookie
	function join_cookie()
	{
	    foreach( $_COOKIE as $k=>$v )
	    {
			$d[] =$k."=".urlencode($v);
	    }
		$data = implode("; ",$d);
		return $data;
	}

	//function

}
