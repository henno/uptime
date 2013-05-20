<?
internet_check();
http_check('https://www.example.com/admin/login', 'Username');
tcp_check('example.com', '3389');
dns_check('example.com.', '12.34.56.78');

function internet_check()
{
	set_error_handler('handleError');
	try {
		fsockopen("www.google.ee", 80);
	} catch(Exception $e) {
		$fp = fopen('log', 'a');
		fputs($fp, date('[Y-m-d H:i:s] ').$e->getMessage()."\n");
		fclose($fp);
		die();
	}
}
function handleError($errno, $errstr, $errfile, $errline, array $errcontext)
{
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }
    throw new ErrorException(date('m.d H:i '). $errstr, 0, $errno, $errfile, $errline);
}
function dns_check($host, $ip)
{
	set_error_handler('handleError');
	$resolved_ip = gethostbyname($host);
	if( $resolved_ip == $host )
		echo "Hosti $host IP leidmine nurjus\n";
	elseif( $resolved_ip != $ip )
		echo "Hosti $host IP kontrollimine andis '$ip' asemel tulemuseks hoopis '$resolved_ip'\n";
}
function http_check($url, $find) {
	set_error_handler('handleError');
	$url_array = parse_url($url);
	$host = isset($url_array['host']) ? $url_array['host'] : $_SERVER['SERVER_NAME'];
	$path = isset($url_array['path']) ? $url_array['path'] : '/';
	if( !isset($url_array['scheme']) or $url_array['scheme'] == 'http' ) {
		$port = isset($url_array['port']) ? $url_array['port'] : 80;
		try {
			$fp = fsockopen($host, $port, $errno, $errstr, 10) or die("Ei saanud avada $host\m");
		} catch (Exception $e){
			echo date('H:i') . " URL-i $url kontrollimine nurjus:\n";
		}
	}
	elseif( $url_array['scheme'] == 'https' ) {
		$port = isset($url_array['port']) ? $url_array['port'] : 443;
		try {
			$fp = fsockopen("ssl://$host", $port, $errno, $errstr, 10) or die("Ei saanud avada $host\n");
		} catch (Exception $e){
			echo "Testing $url failed:\n";
		}
	}
	if (!isset($fp)) {
		echo "$errstr ($errno)\n";
	} else {
		$header = "GET $path HTTP/1.1\r\n";
		$header .= "Host: $host\r\n";
		$header .= "Connection: close\r\n\r\n";

		try {
			fputs($fp, $header);
		} catch (Exception $e){ 
			echo "Testing TCP $host:$port failed: $errstr\n";
		}
		$str = '';
		while (!feof($fp)) {
			$str .= fgets($fp);
		}
		#print_r($str);
		fclose($fp);
		if(strpos($str, $find) === false)
		{
			$short_url = str_replace('https://', '', $url);
			$short_url = str_replace('http://', '', $short_url);
			echo "$short_url != '$find'. Sisu: $str\n";
		}
	}
}

function tcp_check($host, $port) {
	set_error_handler('handleError');
	try {
		$fp = fsockopen($host, $port, $errno, $errstr, 1);
		fclose($fp);
	} catch (Exception $e){
		echo "Testing TCP $host:$port failed: $errstr\n";
	}
	return true;
}
