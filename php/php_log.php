<?php
	header("Access-Control-Allow-Origin:*");
	class MagicBlue
	{
		public function __construct($filepath,$filename="")
		{
			if ($filename==""){
				$filename = date('Y-m-d-h');
			}
			$this->filename = $filename;
			$this->filepath = $filepath;
			$this->header = array();
		}

		public function Flow()
		{
			$arr = array(
				'HTTP_HOST',
				'HTTP_USER_AGENT',
				'HTTP_ACCEPT',
				'HTTP_ACCEPT_LANGUAGE',
				'HTTP_ACCEPT_ENCODING',
				'HTTP_REFERER',
				'HTTP_COOKIE',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_CONNECTION');
			$HTTP_Method = $_SERVER['REQUEST_METHOD'];
			$server = $_SERVER;
			if(!file_exists($this->filepath))
			{
				mkdir($this->filepath,0777);
			}
			$Allfilepath = $this->filepath.'/'.$this->filename;
			foreach($arr as $value)
			{
				$this->header[$value] = $server[$value];
			}
			$head = '';
			foreach ($this->header as $key => $value)
			{
				if(stripos($key, 'HTTP_') == -1)
				{
					$key = ucwords(strtolower($key));
				}else
				{
					$key = ucwords(strtolower(substr($key, 5)));
				}
				$head.= $key.': '.$value."\r\n";
			}
			$request_url = $_SERVER['REQUEST_URI'];
			$protocol = $_SERVER['SERVER_PROTOCOL'];
			if(isset($_POST))
			{
				$post = file_get_contents('php://input');
			}
			$ip = $_SERVER['REMOTE_ADDR'];
			$time = date('Y/m/d h:i:s');
			$content = "====================\n";
			$content .= $ip."\t".$time."\n";
			$content .= $HTTP_Method.' '.$request_url.' '.$protocol."\r\n";
			$content .= $head."\n\n";
			$content .= $post."\n\n";
			//////////
			$conn = mysql_connect("localhost","root","az2026451245");
			mysql_select_db("avs",$conn);
			$sql = "select top 20 event_time, argument from mysql.general_log where command_type='Query' and argument not like '%general\\_log%' and argument not like '%log\\_output%';";
			$result = mysql_query($sql,$conn);
			$content .= print_r(mysql_fetch_row($result),true)."\r\n";
			mysql_close($conn);
			//////////
			$this->WriteFile($Allfilepath,$content,FILE_APPEND);
		}

		public function WriteFile($filepath,$content,$FILE_APPEND=FILE_APPEND)
		{
			file_put_contents($filepath,$content,$FILE_APPEND);
		}

		public function ClearFile()
		{
			file_put_contents($this->filepath.'/'.$this->filename,"");
			header("Location: view-source:".$_SERVER['REMOTE_ADDR'].$this->filepath.'/'.$this->filename);
		}
	}
	$action = @$_GET['ac'];
	$Catchs = new MagicBlue('./virink/','log.txt');
	if ($action == "clear"){
		$Catchs->ClearFile();
	}else{
		$Catchs->Flow();
	}
?>
