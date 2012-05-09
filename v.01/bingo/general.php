<?php

require_once('../devinc.common.php');
require_once(FRAMEWORK_DIR . '/bingo/devinc.bingo.php');

date_default_timezone_set("Asia/Shanghai");

$user['id'] = 1;
$user['screen_name'] = 'duanyong';

$_POST['confirm'] = 1;

//得到参数
if (s_bad_post("key", $akey)) {
    //应用的key
    return s_action_json(array(
        "error"     => 1,
        "errmsg"    => "未指定APP KEY",
    ));
}


if (s_bad_post('name', $name)) {
    //应用名称
    return s_action_json(array(
        "error"     => 1,
        "errmsg"    => "未指定应用程序名称",
    ));
}


if (s_bad_post('start', $start)) {
    //应用启止日期
    return s_action_json(array(
        "error"     => 1,
        "errmsg"    => "未指定活动启止日期",
    ));
}


if (s_bad_post('stop', $stop)) {
    //应用启止日期
    return s_action_json(array(
        "error"     => 1,
        "errmsg"    => "未指定活动启止日期",
    ));
}


if (s_bad_post('odds', $odds, 'int0')) {
    //是否平均分布
    return s_action_json(array(
        "error"     => 1,
        "errmsg"    => "未指定是否平均分布",
    ));
}


if (s_bad_post('items', $items, 'array')) {
    //指定活动奖品名称
    return s_action_json(array(
        "error"     => 1,
        "errmsg"    => "未指定奖品名称",
    ));
}


if (s_bad_post('nums', $nums, 'array')) {
    //指定活动奖品数量
    return s_action_json(array(
        "error"     => 1,
        "errmsg"    => "未指定奖品数量",
    ));
}

////////////////////////////////////////////////////////////////////////////////
//检验参数是否有效
//


//检查启止日期是否正确
if ($start !== date('Y-m-d', ( $time1 = strtotime($start) ))
    || $stop !== date('Y-m-d', ( $time2 = strtotime($stop) ))
    || $time2 < $time1
) {
    $params["error"]    = 2;
    $params["errmsg"]   = "启止日期({$start}, {$stop})不正确，请重新检查，例:2012-05-04";
}


//key是数据库中是否存在
$sql = sprintf("select count(*) from %s_bingo_config where `key`='%s' limit 1", FRAMEWORK_DBPREFIX, $akey);

if (s_db_one($sql) >= 1) {
    //key已经在中奖系统中存在
    $params['error']  = 2;
    $params['errmsg'] = "活动key({$akey})已经在中奖系统中存在，不能重复添加";
}


//检查奖品名单与数量是否对应
$pos = count($items);

while (( -- $pos ) >= 0
    && isset($items[$pos])
) {
    if (!isset($nums[$pos])) {
        //在名单对应的位置上并没有数量，需要提示
        $params['error']  = 3;
        $params['errmsg'] = "奖品名单对应的下标({$pos}=>{$items[$pos]})并没有奖品数量与其对应";

        break;
    }
}


$time1   = strtotime("+1 day", $time1) -1;
$time2   = strtotime("+1 day", $time2) -1;

$daytime = 24 * 3600;
$days    = ( $time2 - $time1 ) / $daytime + 1;

$pos    = count($items);
$sum    = 0;
$names  = array();

//得到奖品总数
while ( ( -- $pos ) >= 0
    && ( $name = $items[$pos] )
    && ( $num = $nums[$pos] )
) {
    $sum += intval($num);

    //产生新的数组，用md5值来产生中奖key
    $names[md5($name)] = array(
        "name"  => $name,
        "count" => $num,
    );
}


//计算活动天数及每天中奖数量
$avg = floor($sum / $days);

if (s_bad_post('confirm', $confirm, 'int')) {

    $params['key']      = $akey;
    $params['name']     = $name;
    $params['start']    = $start;
    $params['stop']     = $stop;
    $params['odds']     = $odds;
    $params['items']    = $items;
    $params['nums']     = $nums;

    $params['confirm']  = 1;
    $params['avg']      = $avg;

    //未确认
    return s_action_json($params);
}


//将配置信息存储起来
$data = array(
    'name'  => $name,
    'key'   => $akey,
    'start' => $start,
    'stop'  => $stop,
    'odds'  => $odds,
    'uid'   => $user['id'],
    'uname' => $user['screen_name'],
    'sum'   => $sum,
    'fdate' => date('Y-m-d'),
    'ftime' => date('Y-m-d H:i:s'),
);
//$sql = sprintf("insert into `%s_bingo_config (`name`, `key`, `start`, `stop`, `odds`, `uid`, `uname`, `sum`, `fdate`, `ftime`) values('%s', '%s', %s, '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s')`;", 
    //FRAMEWORK_DBPREFIX, $name, $akey, $start, $stop, $odds, $user['id'], $user['screen_name'], $sum, date('Y-m-d'), date('Y-m-d H:i:s'));

$cid = s_db(sprintf("%s_bingo_config:insert", FRAMEWORK_DBPREFIX), $data);


//产生sum个优惠码
$codes = array();
foreach ($names as $key => $data) {
    $max    = $data["count"];
    $arr    = array_fill(0, $max, $key);
    $codes  = array_merge($arr, $codes);
}


//打乱顺序，再分配中奖时间
shuffle($codes);
shuffle($codes);
shuffle($codes);
shuffle($codes);


//从活动开始按每天给中奖代码分配中奖时间

//中奖代码下标
$time  = $time1;
$html  = "";

for ($i=0, $len=count($codes); $i<$len; ++$i) {
    $time = $time1 + intval($i / $avg) * $daytime;
    $code = $codes[$i];
    $name = $names[$code]['name'];

    $html .= sprintf("\ninsert into `%s_bingo_detail` (`key`, `date`, `code`, `name`, `status`) values('%s', '%s', '%s', '%s', '%s');", 
        FRAMEWORK_DBPREFIX, $akey, $time, $code, $name, FRAMEWORK_BINGO_STATUS);
}

echo "#name:{$name} key:{$akey} start:{$start} stop:{$stop} count:{$len} avg:{$avg}#\n";
echo $html;

