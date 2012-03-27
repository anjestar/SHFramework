<?php
//封装SSO类文件

require_once('SSOCookie.class.php');
include_once('SSOConfig.php');

class SSOClient {
 	private $uniqueid  = '';
 	function login_info() {
		$cookie = new SSOCookie();//配置文件cookie.conf的路径
		$userinfo = array();
		if(false === $cookie->getCookie($userinfo)) 
		{//取cookie中的用户信息
			echo $cookie->getError();
		}
		return $userinfo;
	}
	
	//用户是否登录
	function isLogined() {
		$userinfo  = self::login_info();
		if($userinfo['uniqueid']) {
			$this->uniqueid = $userinfo['uniqueid'];
			return true;
		}
		else {
			return false;
		}
	}
	
	//取得 uniqueid
	function getUniqueid() {
		return $this->uniqueid;
	}
	
	//取得登录用户的详细信息
	function getUserInfo() {
		$uniqueid  = $this->uniqueid;
		if(!$uniqueid) {
			return false;
		}
		else {
			$userinfo  = get_user($uniqueid);
			return $userinfo ? $userinfo : "";
		}
	}
	
	function GetSinaInfo()
	{
		if(self::isLogined())
		{
			return $this->uniqueid; 
		}
		else
		{
			return 0;
		}
	}
	
	function GetSinaInfoAll()
	{
		if(self::isLogined())
		{		
			return self::getUserInfo(); 
		}
	}
	function CheckLogin()
	{
		if (!self::isLogined())
		{
			header("Location:index.php");
		}
	}
 }