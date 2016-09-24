<?php

error_reporting(0);

define('LOG_FILENAME','log.txt');

function waf()
{
	//獲取Header信息
    if (!function_exists('getallheaders')) {
        function getallheaders() {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_')
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
            return $headers;
        }
    }

    $get = $_GET;
    $post = $_POST;
    $cookie = $_COOKIE;
    $header = getallheaders();
    $files = $_FILES;
	
    $ip = $_SERVER["REMOTE_ADDR"];
    $method = $_SERVER['REQUEST_METHOD'];
    $filepath = $_SERVER["SCRIPT_NAME"];

	//清空上傳文件內容
    foreach ($_FILES as $key => $value) {
        $files[$key]['content'] = file_get_contents($_FILES[$key]['tmp_name']);
        file_put_contents($_FILES[$key]['tmp_name'], "virink");
    }

	//這是一個小Bug
    unset($header['Accept']);

    $input = array("Get"=>$get, "Post"=>$post, "Cookie"=>$cookie, "File"=>$files, "Header"=>$header);

    $pattern = "select|insert|update|delete|and|or|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile|dumpfile|sub|hex";
    $pattern .= "|file_put_contents|fwrite|curl|system|eval|assert";
    $pattern .="|passthru|exec|system|chroot|scandir|chgrp|chown|shell_exec|proc_open|proc_get_status|popen|ini_alter|ini_restore";
    $pattern .="|`|dl|openlog|syslog|readlink|symlink|popepassthru|stream_socket_server|assert|pcntl_exec";
    $vpattern = explode("|",$pattern);

    $bool = false;
    foreach ($input as $k => $v) {
        foreach($vpattern as $value){
            foreach ($v as $kk => $vv) {
                if (preg_match( "/$value/i", $vv )){
                    $bool = true;
                    logging($input);
                    break;
                }
            }
            if($bool) break;
        }
        if($bool) break;
    }
        
}

function logging($var){
    file_put_contents(LOG_FILENAME, "\r\n".time()."\r\n".print_r($var, true), FILE_APPEND);
    // die();
    // unset($_GET);
    // unset($_POST);
    // unset($_COOKIE);
}

waf();

?>
