<?php
error_reporting(0);
date_default_timezone_set('PRC');

header('Content-Type: text/html; charset=utf-8');
header('PHPWAF:By_Virink');

define('PASSWORD', "virink");
define('DB_HOST', "localhost");
define('DB_USER', "root");
define('DB_PASS', "root");
define('DB_TABLE', "vphpwaf");

class VPhpWaf {

    function __construct()
    {
        if(!file_exists('jquery.min.js'))
            file_put_contents(dirname(__FILE__).'/jquery.min.js',file_get_contents('http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js'));
        $this->action();
    }

    function action(){
        if (isset($_GET['vgetnew']) && @$_GET['vpasswd'] === PASSWORD) {
            $this->getnew();
        }else if(@$_GET['vphpwaf'] === PASSWORD){
            $this->admin();
        }else{
            $this->waf();
        }
    }

    function waf()
    {
        if (!function_exists('getallheaders')) {
            function getallheaders() {
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_')
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
                unset($header['Accept']);
                return $headers;
            }
        }

        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
        $this->header = getallheaders();
        $this->ip = $_SERVER["REMOTE_ADDR"];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->filepath = $_SERVER["SCRIPT_NAME"];

        unset($this->header['Accept']);

        $this->input = array("Get"=>$this->get, "Post"=>$this->post, "Cookie"=>$this->cookie, "Header"=>$this->header);

        $pattern = "select|insert|update|delete|and|or|eval|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile|dumpfile|sub|hex";
        $pattern .= "|file_put_contents|fwrite|curl|system|eval|assert";
        $pattern .="|passthru|exec|system|chroot|scandir|chgrp|chown|shell_exec|proc_open|proc_get_status|popen|ini_alter|ini_restore";
        $pattern .="|`|dl|openlog|syslog|readlink|symlink|popepassthru|stream_socket_server|assert|pcntl_exec";
        $vpattern = explode("|",$pattern);

        $bool = false;
        foreach ($this->input as $k => $v) {
            foreach($vpattern as $value){
                foreach ($v as $kk => $vv) {
                    if (preg_match( "/$value/i", $vv )){
                        $bool = true;
                        $this->error['method'] = $k;
                        $this->error['kv'] = $kk."=".$vv;
                        $this->error['flag'] = $value;
                        $this->debugop($this->error);
                        $this->log($value);
                        break;
                    }
                }
                if($bool) break;
            }
            if($bool) break;
        }
            
    }

    function log($var){
        $sql = "INSERT INTO log (date, header, get, post, cookie, ip, method, filepath, error) VALUES (".time().", '".$this->vescapejson($this->header)."', '".$this->vescapejson($this->get)."', '".$this->vescapejson($this->post)."', '".$this->vescapejson($this->cookie)."', '$this->ip', '$this->method', '$this->filepath', '".$this->vescapejson($this->error)."')";
        $conn = mysql_connect(DB_HOST, DB_USER, DB_PASS);
        mysql_select_db(DB_TABLE, $conn);
        mysql_query($sql, $conn);
        mysql_close($conn);
    }

    function vescapejson($var){
        $var = json_encode($var);
        if(!get_magic_quotes_gpc()){
            $var=addslashes($var);
        }
        return $var;
    }

    function install(){
        // 懒的写了
        //  CREATE DATABASE `vphpwaf` /*!40100 DEFAULT CHARACTER SET utf8 */;
        /*
        CREATE TABLE `log` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` int(11) NOT NULL DEFAULT '0',
          `header` text,
          `get` text,
          `post` text,
          `cookie` text,
          `session` text,
          `ip` varchar(15) DEFAULT NULL,
          `method` varchar(20) DEFAULT NULL,
          `filepath` text,
          `error` text,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        */
    }

    function admin(){
        if($_GET['vphpwaf'] !== PASSWORD){
            $this->waf();
            exit();
        }
        $html = base64_decode('PCFET0NUWVBFIEhUTUw+DQo8aHRtbD4NCjxoZWFkPg0KICAgIDx0aXRsZT4gVi1QSFBXQUYgVmVyIDEuMCAtLS0tIE1hZGUgQnkgVmlyaW5rPC90aXRsZT4NCiAgICA8bWV0YSBjaGFyc2V0PSJ1dGYtOCI+DQoJPHNjcmlwdCBzcmM9ImpxdWVyeS5taW4uanMiPjwvc2NyaXB0PiANCgk8c3R5bGUgdHlwZT0idGV4dC9jc3MiPg0KCQlib2R5eyBiYWNrZ3JvdW5kOiAjMzMzOyB9DQoJCXVsIGxpIHsgbGlzdC1zdHlsZS10eXBlOm5vbmU7IH0gDQoJCSNnby10b3Agew0KCQkJd2lkdGg6IDYwcHg7DQoJCQloZWlnaHQ6IDYwcHg7DQoJCQl0ZXh0LWFsaWduOiBjZW50ZXI7DQoJCQlsaW5lLWhlaWdodDogMzBweDsNCgkJCWRpc3BsYXk6IG5vbmU7DQoJCQlwb3NpdGlvbjogZml4ZWQ7DQoJCQlyaWdodDogMzBweDsNCgkJCWJvdHRvbTogMzBweDsNCgkJCWNvbG9yOiAjZmZmOw0KCQkJei1pbmRleDogMTAwOw0KCQl9DQoJCS5wYWdlew0KCQkJd2lkdGg6MTAwJTsNCgkJCWhlaWdodDogMTAwJTsNCgkJCS8qdGV4dC1hbGlnbjogY2VudGVyOyovDQoJCX0NCgkJLm1lbnUgew0KCQkJYmFja2dyb3VuZDogIzU1NTsNCgkJCXRleHQtYWxpZ246IGNlbnRlcjsNCgkJCWNvbG9yOiNmZmY7DQoJCX0NCgkJLmxpc3QtZ3JvdXB7DQoJCQl3aWR0aDoxMDAlOw0KCQkJaGVpZ2h0OmF1dG87DQoJCQliYWNrZ3JvdW5kOiAjY2NjOw0KCQl9DQoJCS5saXN0LXRpdGxlew0KCQkJZm9udC1zaXplOiAyNHB4Ow0KCQl9DQoJCS5oaWRkZW4gew0KCQkJZGlzcGxheTpub25lOw0KCQl9DQoJPC9zdHlsZT4NCjwvaGVhZD4NCjxib2R5Pg0KPGRpdiBjbGFzcz0icGFnZSI+DQoJPGRpdiBjbGFzcz0ibWVudSI+DQoJCTxoMT5WLXBocHdhZjwvaDE+DQoJCTxzcGFuIG9uY2xpY2s9InN0YXJ0KCk7Ij7lvIDlp4s8L3NwYW4+DQoJCTxzcGFuIG9uY2xpY2s9InN0b3AoKTsiPuWBnOatojwvc3Bhbj4NCgk8L2Rpdj4NCgk8ZGl2IGNsYXNzPSJsaXN0LWdyb3VwIj48L2Rpdj4NCjwvZGl2Pg0KPGEgaWQ9ImdvLXRvcCIgaHJlZj0iIyIgc3R5bGU9ImRpc3BsYXk6IG5vbmU7Ij5Ub3A8L2E+IA0KPHNjcmlwdD4NCglzaG93ID0gbmV3IEFycmF5KCk7DQoJdmFyIGlkID0gMDsNCgl0ID0gMDsNCgkkKHdpbmRvdykuc2Nyb2xsKGZ1bmN0aW9uICgpIHsNCgkgICAgaWYgKCQodGhpcykuc2Nyb2xsVG9wKCkgPiAxMDApew0KCQkJJCgnI2dvLXRvcCcpLmZhZGVJbigxMDApOw0KCSAgICB9ZWxzZXsNCgkJCSQoJyNnby10b3AnKS5mYWRlT3V0KDEwMCk7DQoJICAgIH0NCgl9KTsNCglmdW5jdGlvbiBzaG93bW9yZShpZCl7DQoJCWlmKCBzaG93W2lkXSApew0KCQkJJCgnI2xtJytpZCkuZmFkZU91dCgxMDApOw0KCQkJc2hvd1tpZF0gPSBmYWxzZTsNCgkJfWVsc2V7DQoJCQkkKCcjbG0nK2lkKS5mYWRlSW4oMTAwKTsNCgkJCXNob3dbaWRdID0gdHJ1ZTsNCgkJfQ0KCX0NCglmdW5jdGlvbiBnZXRuZXcoKXsNCgkJJC5nZXQoInBocHdhZi5waHAiLHsidmdldG5ldyI6aWQsICJ2cGFzc3dkIjoidmlyaW5rIn0sZnVuY3Rpb24oZGF0YSl7DQoJCQl2YXIgb2JqID0gbmV3IEZ1bmN0aW9uKCJyZXR1cm4iICsgZGF0YSkoKTsNCgkJCWFkZGRhdGEob2JqKTsNCgkJfSk7DQoJfQ0KCWZ1bmN0aW9uIGFkZGRhdGEob2JqKXsNCgkJb2JqLmZvckVhY2goZnVuY3Rpb24oZSl7DQoJCSAgICBpbnNlcnREaXYoZSk7ICANCgkJfSkgDQoJfQ0KCWZ1bmN0aW9uIGluc2VydERpdihqc29uKSB7DQoJCWlkID0ganNvbi5pZDsNCgkJdmFyIGh0bWwgPSAnJzsNCgkJdmFyIG5ld0RhdGUgPSBuZXcgRGF0ZSgpOw0KCQluZXdEYXRlLnNldFRpbWUoanNvbi5kYXRlICogMTAwMCk7DQoJCXZhciB0dHQgPSBuZXdEYXRlLnRvTG9jYWxlRGF0ZVN0cmluZygpOw0KCQlodG1sICs9ICc8ZGl2IGlkPSJsJytqc29uLmlkKyciIGNsYXNzPSJsaXN0LWJsb2NrIj48ZGl2IGNsYXNzPSJsaXN0LXRpdGxlIiBvbmNsaWNrPSJzaG93bW9yZSgnK2pzb24uaWQrJyk7Ij4nOw0KCQlodG1sICs9IGpzb24uaWQrJy0tJytqc29uLmlwKyctLScrdHR0KyctLScranNvbi5tZXRob2QrJy0tJytqc29uLmVycm9yKyc8L2Rpdj4nOw0KCQlodG1sICs9ICc8ZGl2IGlkPSJsbScranNvbi5pZCsnIiBjbGFzcz0ibGlzdC1tb3JlIiBzdHlsZT0iZGlzcGxheTogbm9uZTsiPjx1bD4nOw0KCQlodG1sICs9ICc8bGk+Jytqc29uLmhlYWRlcisnPC9saT4nOw0KCQlodG1sICs9ICc8bGk+Jytqc29uLmdldCsnPC9saT4nOw0KCQlodG1sICs9ICc8bGk+Jytqc29uLnBvc3QrJzwvbGk+JzsNCgkJaHRtbCArPSAnPGxpPicranNvbi5jb29raWUrJzwvbGk+JzsNCgkJaHRtbCArPSAnPGxpPicranNvbi5maWxlcGF0aCsnPC9saT4nOw0KCQlodG1sICs9ICc8L3VsPjwvZGl2PjwvZGl2Pic7DQoJCSQoJy5saXN0LWdyb3VwJykucHJlcGVuZChodG1sKTsgIA0KCX0gCQ0KCWZ1bmN0aW9uIHN0YXJ0KCl7DQoJCXQgPSBzZXRJbnRlcnZhbCgiZ2V0bmV3KCkiLDUwMDApOw0KCX0NCglmdW5jdGlvbiBzdG9wKCl7DQoJCXdpbmRvdy5jbGVhckludGVydmFsKHQpOw0KCX0NCjwvc2NyaXB0Pg0KPC9ib2R5Pg0KPC9odG1sPg==');
    echo $html;
    }

    function getnew(){
        $id = $_GET['vgetnew'];
        $sql = "select * from log where date>".strtotime(date("Y-m-d"))." and id>".intval($id) . " ORDER BY id ASC";
        $conn = mysql_connect(DB_HOST, DB_USER, DB_PASS);
        mysql_select_db(DB_TABLE, $conn);
        $rs = mysql_query($sql, $conn);
        $result = array();
        while($row=mysql_fetch_assoc($rs)){
            array_push($result, $row);
        }
        mysql_close($conn);
        echo json_encode($result);
    }

}
new VPhpWaf();
?>
