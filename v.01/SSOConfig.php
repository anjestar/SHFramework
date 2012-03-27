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
        var $SERVICE   = "active";     //�������ƣ���Ʒ���ƣ�Ӧ�ú�entry����һ��
        var $ENTRY     = "active";     //Ӧ�ò�Ʒentry �� pin , ��ȡ�û���ϸ��Ϣʹ�ã���ͳһע��䷢��
        var $PIN           = "c1e5d8563e4cf8ccbcda8514b11b2045";
        var $COOKIE_DOMAIN = ".sina.com.cn";  //domain of cookie, ���������ڵĸ����硰.sina.com.cn������.ucmail.com��
        var $USE_SERVICE_TICKET = false; // ���ֻ��Ҫ����sina.com.cn���cookie�Ϳ��������û���ݵĻ�����������Ϊfalse����������Ҫ��֤service ticket��ʡһ��http�ĵ���
}
?>