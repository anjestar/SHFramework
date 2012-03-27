<?php
//��װSSO���ļ�

require_once('SSOCookie.class.php');
include_once('SSOConfig.php');

class SSOClient {
 	private $uniqueid  = '';
 	function login_info() {
		$cookie = new SSOCookie();//�����ļ�cookie.conf��·��
		$userinfo = array();
		if(false === $cookie->getCookie($userinfo)) 
		{//ȡcookie�е��û���Ϣ
			echo $cookie->getError();
		}
		return $userinfo;
	}
	
	//�û��Ƿ��¼
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
	
	//ȡ�� uniqueid
	function getUniqueid() {
		return $this->uniqueid;
	}
	
	//ȡ�õ�¼�û�����ϸ��Ϣ
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