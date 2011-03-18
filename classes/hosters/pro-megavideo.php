<?php if ( ! defined( 'PREMIUMGENERATOR_VERSION' ) ) exit( 'No direct script access allowed' );

/*

<object width="432" height="351">
	<param name="movie" value="http://www.megavideo.com/v/ULZS4WJR0e1de24f59b77fd39cc53941da76fd2a.1307741634.0"></param>
	<param name="wmode" value="transparent"></param>
	
	<embed src="http://www.megavideo.com/v/ULZS4WJR0e1de24f59b77fd39cc53941da76fd2a.1307741634.0" type="application/x-shockwave-flash" wmode="transparent" width="432" height="351"></embed>
</object>

*/

class PremiumGenerator_Hoster_Megavideo extends PremiumGenerator_Hoster
{
	public $request_class = 'curl';
	
	private $cookie_file = 'cookie_mv_data.php';
	private $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
	
	private $login_url = 'http://www.megavideo.com/?s=signup';
	private $logout_url = 'http://www.megavideo.com/?logout=1';
	private $player_url = "http://www.megavideo.com/xml/player_login.php";

	public function is_standard($url)
	{
		preg_match('/http:\/\/([a-z0-9]+)\.megavideo\.com\/\?v=([A-Z0-9]{8})/i', $url, $result);
		return isset($result[2]) ? TRUE : FALSE;
	}

	public function is_premium($url)
	{
		preg_match('/http:\/\/([a-z0-9]+)\.megavideo\.com\/files\/([a-z0-9]{32})\/(.+)/i', $url, $result);
		return isset($result[3]) ? TRUE : FALSE;
	}
	
	public function is_online($url)
	{
		$this->request->follow_redirects = FALSE;
		
		$r = $this->request->get( $link );
		
		return ( strstr( 'This video has been removed due to infringement.', $r->body ) ? FALSE : TRUE );
	}

	public function login($username, $password)
	{
		$post_data = array(
			'nickname'	=> $username,
			'password'	=> $password,
			'action'	=> "login"
		);
		
		$r = $this->request->post( $this->login_url, $post_data );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}

		return strpos($r->body, $username);
	}

	public function logout()
	{
		$r = $this->request->get( $this->logout_url );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}

		@unlink( $this->cookie_file );
	}

	public function generate( $link )
	{
		$user_id = $this->get_user_id($link);
		$link_id = $this->get_link_id($link);

		// Controllo che il link sia valido.
		if ( ! $this->is_valid($link) )
		{
			return 'invalid-id';
		}
		
		// Controllo che l'utente sia premium e che il video esista.
		if ( ! $user_id )
		{
			return 'invalid-userid';
		}

		$r = $this->request->get( "{$this->player_url}?u={$user_id}&v={$link_id}" );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}

		preg_match('|downloadurl="(.+)"|U', $r->body, $result);

		return urldecode($result[1]);
	}
	
	private function get_user_id($link)
	{
		$r = $this->request->get($link);
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}
		
		preg_match('|flashvars.userid = "(.+)";|U', $r->body, $result);
		
		return (isset($result[1])) ? $result[1] : false;
	}
	
	private function get_link_id($url)
	{
		preg_match('/http:\/\/([a-z0-9]+)\.megavideo\.com\/\?v=([A-Z0-9]{8})/i', $url, $result);
		return isset($result[2]) ? $result[2] : FALSE;
	}
	
	private function get_vcode($link)
	{
		$r = $this->request->get($link);
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}

		preg_match('|flashvars.embed = "(.+)";|U', $r->body, $result);
		
		return urldecode($result[1]);
	}
	
	
	private function get_xml_attribute($xml, $name)
	{
		preg_match_all("/[\s]+$name\=\"(.+)\"/isU", $xml, $matches);
		return $matches[1][0];
	}

	public function get_premium_flv($url)
	{
		$vcode = $this->get_link_id($url);

		$vlink = "http://www.megavideo.com/xml/videolink.php?v={$vcode}&width=1278&id=" . time() . "&u=" . $this->getUserId($url);

		$vxml = file_get_contents($vlink);

		$title = $this->get_xml_attribute($vxml, "title");
		$duration = $this->get_xml_attribute($vxml, "runtimehms");
		$size = $this->get_xml_attribute($vxml, "size");
		$k1 = $this->get_xml_attribute($vxml, "k1");
		$k2 = $this->get_xml_attribute($vxml, "k2");
		$un = $this->get_xml_attribute($vxml, "un");
		$s = $this->get_xml_attribute($vxml, "s");

		return "http://www{$s}.megavideo.com/files/" . $this->decrypt( $un, (int)$k1, (int)$k2 ) . "/";
		//return "http://www.megavideo.com/v/" . $vcode . self::decrypt( $un, (int)$k1, (int)$k2 );
	}

	private function decrypt($str, $key1, $key2)
	{
		$binblock = array();

		for($i = 0; $i < strlen($str); ++$i)
		{
			switch($str[$i])
			{
				case "0":
					$binblock[] = "0000";
				break;

				case "1":
					$binblock[] = "0001";
				break;

				case "2":
					$binblock[] = "0010";
				break;

				case "3":
					$binblock[] = "0011";
				break;

				case "4":
					$binblock[] = "0100";
				break;

				case "5":
					$binblock[] = "0101";
				break;

				case "6":
					$binblock[] = "0110";
				break;

				case "7":
					$binblock[] = "0111";
				break;

				case "8":
					$binblock[] = "1000";
				break;

				case "9":
					$binblock[] = "1001";
				break;

				case "a":
					$binblock[] = "1010";
				break;

				case "b":
					$binblock[] = "1011";
				break;

				case "c":
					$binblock[] = "1100";
				break;

				case "d":
					$binblock[] = "1101";
				break;

				case "e":
					$binblock[] = "1110";
				break;

				case "f":
					$binblock[] = "1111";
				break;
			}

		}

		$binblock = join("", $binblock);

		$ciphers = array();

		for($i = 0; $i < 384; ++$i)
		{
			$key1 = ($key1 * 11 + 77213) % 81371;
			$key2 = ($key2 * 17 + 92717) % 192811;
			$ciphers[] = ($key1 + $key2) % 128;
		}



		for($i = 256; $i >= 0; --$i)
		{
			$cipher = $ciphers[$i];

			$offset = $i % 128;

			$tmp = $binblock[$cipher];

			$binblock[$cipher] = $binblock[$offset];
			$binblock[$offset] = $tmp;
		}



		for($i = 0; $i < 128; ++$i)
		{
			$binblock[$i] = $binblock[$i] ^ $ciphers[$i + 256] & 1;
		}



		$chunks = array();

		for($i = 0; $i < strlen($binblock); $i += 4)
		{
			$chunks[] = substr( $binblock, $i, 4 );
		}



		$decrypted = array();

		for($i = 0; $i < count($chunks); ++$i)
		{
			switch($chunks[$i])
			{
				case "0000":
					$decrypted[] = "0";
				break;

				case "0001":
					$decrypted[] = "1";
				break;

				case "0010":
					$decrypted[] = "2";
				break;

				case "0011":
					$decrypted[] = "3";
				break;

				case "0100":
					$decrypted[] = "4";
				break;

				case "0101":
					$decrypted[] = "5";
				break;

				case "0110":
					$decrypted[] = "6";
				break;

				case "0111":
					$decrypted[] = "7";
				break;

				case "1000":
					$decrypted[] = "8";
				break;

				case "1001":
					$decrypted[] = "9";
				break;

				case "1010":
					$decrypted[] = "a";
				break;

				case "1011":
					$decrypted[] = "b";
				break;

				case "1100":
					$decrypted[] = "c";
				break;

				case "1101":
					$decrypted[] = "d";
				break;

				case "1110":
					$decrypted[] = "e";
				break;

				case "1111":
					$decrypted[] = "f";
				break;
			}
		}

		return join("", $decrypted);
	}

	public function remote_upload_auth()
	{
		$post_data = array(
			"action"		=> "step2",
			"title"			=> "Titolo",
			"description"	=> "Descrizione",
			"tags"			=> "Tags",
			"language"		=> "1",
			"channel"		=> "1",
		);

		$r = $this->curl->post('http://www.megavideo.com/?c=upload', $post_data);
		
		// http://www464.megavideo.com/upload_video.php?UPLOAD_IDENTIFIER=3039911231557864
		preg_match('|"http://(.+).megavideo.com/upload_video.php\?UPLOAD_IDENTIFIER=(.+)"|U', $r->body, $result);

		return "http://{$result[1]}.megavideo.com/upload_video.php?UPLOAD_IDENTIFIER={$result[2]}";
	}

	public function remote_upload($filename)
	{
		$post_data = array(
			"action"		=> "submit",
			"title"			=> "Titolo",
			"message"		=> "Descrizione",
			"tags"			=> "Tags",
			"language"		=> "1",
			"channels"		=> "23;",
			"file"			=> "@{$filename}",
			"private"		=> "1"
		);

		$file = fopen($filename, 'r');

		$r = $this->request->post($this->remote_upload_auth());

		if ( preg_match('|http://www.megavideo.com/\?v=(.+)|U', $r->body, $result) )
		{
			return "http://www.megavideo.com/?v={$result[1]}";
		}
		else
		{
			return FALSE;
		}
	}
}
