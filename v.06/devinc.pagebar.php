<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.pagebar.php
//	产生pagebar相关的属性
//
//
//	s_pagebar_detail($current, $total, $size=20, $gap=5)
//	    $current    当前页
//	    $total      总数
//	    $size       每页多少条
//	    $gap        显示的页码数
//	    返回pagebar数组
//	        array(
//              "current"   => 2,
//              "first"     => 1,
//              "last"      => 9,
//
//              "nums"      => array(1, 2, 3, 4, 5),
//
//              "prev"      => 1,
//              "next"      => 3,
//          );
//
////////////////////////////////////////////////////////////////////////////////


//返回分页对象
function s_pagebar_detail($current, $total, $size=20, $gap=5) {
    if (s_bad_id($current, $cuttent)
        || s_bad_id($total, $total)
        || s_bad_id($size, $size)
        || s_bad_id($gap, $gap)
    ) {
        return false;
    }


    $diff   = floor($gap / 2);
    $max    = ceil($total / $size);
    $gap    = $gap - 1;

    //检查当前页是否符合最小页或最大页
    if ($current < 1) {
        $current = 1;

    } else if ($current > $max) {
        $current = $max;
    }

    //从末尾处计算开始的页码。页码超出实际页面范围就需要重新计算起止页码
    if (( $to = $current + $diff ) > $max) {
        $to = $max;

        if (( $from = $to - $gap ) < 1) {
            $from = 1;
        }

    } else if (( $from = $to - $gap ) < 1) {
        $from = 1;

        if (( $to = $from + $gap ) > $max) {
            $to = $max;
        }
    }

    return array(
        "current"   => $current,

        "first"     => 1,
        "last"      => $max,

        "nums"      => range($from, $to),

        "prev"      => ( $current - 1 ) < 1 ? 1 : $current - 1,
        "next"      => ( $current + 1 ) > $max ? $max : $current + 1,
    );
}


