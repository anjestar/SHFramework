<?php
/*
 * Created on 2011-5-27
 * author prince
 * function ����ͨ��url ���ݵ�sql �ַ���
 */
class sqlin {
	function __construct() {
	    foreach ($_GET as $get_key=>$get_var) {
			if (is_numeric($get_var)) {
				$_GET[$get_key] = $this->get_int($get_var);
			} 
			else {
				$_GET[$get_key] = $this->get_str($get_var);
			}
		}

		/* ��������POST�����ı��� */
		foreach ($_POST as $post_key=>$post_var) {
			if (is_numeric($post_var)) {
				$_GET[$post_key] = $this->get_int($post_var);
			} 
			else {
				$_GET[$post_key] = $this->get_str($post_var);
			}
		}
	}
	
	/* ���˺��� */
	//���͹��˺���
	function get_int($number) {
		return intval($number);
	}
	
	//�ַ����͹��˺���
	function get_str($string) {
		if (!get_magic_quotes_gpc()) {
			return mysql_escape_string($string);
		}
		return $string;
	}
}
