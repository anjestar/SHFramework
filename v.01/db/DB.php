<?php
/*********************************************************
 * DB extends class
 * connect to primary database & slave database
 *
 * Jory <jorygong@gmail.com>
 * 2006-4-20
**********************************************************/

require_once('MDB2.php');

//Connecting primary database;
function &open_pdb()
{
	$dsn = array(
		'phptype'  => "mysql",
		'username' => $_SERVER['SINASRV_DB4_USER'],
		'password' => $_SERVER['SINASRV_DB4_PASS'],
		'hostspec' => $_SERVER['SINASRV_DB4_HOST'],
		'port'     => $_SERVER['SINASRV_DB4_PORT'],
		'database' => $_SERVER['SINASRV_DB4_NAME'],
	);
	$db = MDB2::connect($dsn);
//	$db->query("SET NAMES 'utf8'");
	if(MDB2::isError($db))
	{
		die($db->getMessage());
	}
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return $db;
}

//Connecting slave database;
function &open_sdb()
{
	$dsn = array(
		'phptype'  => "mysql",
		'username' => $_SERVER['SINASRV_DB4_USER_R'],
		'password' => $_SERVER['SINASRV_DB4_PASS_R'],
		'hostspec' => $_SERVER['SINASRV_DB4_HOST_R'],
		'port'     => $_SERVER['SINASRV_DB4_PORT_R'],
		'database' => $_SERVER['SINASRV_DB4_NAME_R'],
	);
	$db = MDB2::connect($dsn);
//	$db->query("SET NAMES 'utf8'");
	if(MDB2::isError($db))
	{
		die($db->getMessage());
	}
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return $db;
}

//Disconnecting a database
function close_db(&$dbh)
{
	if(is_object($dbh))
	{
		$dbh->disconnect();
		$dbh = "";
	}
}

?>