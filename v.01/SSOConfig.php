<?PHP
/**
 * Sina sso client config file
 * @package  SSOClient
 * @filename SSOConfig.php
 * @author   lijunjie <junjie2@staff.sina.com.cn>
 * @date     2009-11-26
 * @version  1.2
 */

include_once( "SSOCookie.class.php");
class SSOConfig {
        var $SERVICE   = "active";     //服务名称，产品名称，应该和entry保持一致
        var $ENTRY     = "active";     //应用产品entry 和 pin , 获取用户详细信息使用，由统一注册颁发的
        var $PIN           = "c1e5d8563e4cf8ccbcda8514b11b2045";
        var $COOKIE_DOMAIN = ".sina.com.cn";  //domain of cookie, 您域名所在的根域，如“.sina.com.cn”，“.ucmail.com”
        var $USE_SERVICE_TICKET = false; // 如果只需要根据sina.com.cn域的cookie就可以信任用户身份的话，可以设置为false，这样不需要验证service ticket，省一次http的调用
}
?>