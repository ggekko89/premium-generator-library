<?php if ( ! defined( 'PREMIUMGENERATOR_VERSION' ) ) exit( 'No direct script access allowed' );

class PremiumGenerator_Hoster_Megaupload extends PremiumGenerator_Hoster
{
	public $request_class = 'curl';
	
	public $referer = 'http://www.megaupload.com';
	public $cookie_file = "cookie_mu_data.php";
	public $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";

	// --------------------------------------------------------------------

	public function is_standard( $url )
	{
		preg_match( '§http://([a-z0-9]+).megaupload.com/(|[a-z]{2}/)\?d=([A-Z0-9]{8})§U', $url, $result );
		return isset( $result[3] ) ? TRUE : FALSE;
	}
	
	// --------------------------------------------------------------------

	public function is_premium( $url )
	{
		preg_match( '§http://([a-z0-9]+).megaupload.com/files/([a-z0-9]{32})/(.+)§U', $url, $result );
		return isset( $result[3] ) ? TRUE : FALSE;
	}
	
	// --------------------------------------------------------------------

	public function login( $username, $password )
	{
		$post_data = array
		(
			'login'		=> 1,
			'username'	=> $username,
			'password'	=> $password
		);
		
		// Controllo che non sia presente il cookie, in caso vado avanti.
		if ( file_exists( $this->cookie_file ) )
		{
			return true;
		}

		$r = $this->request->post( 'http://www.megaupload.com/?c=login', $post_data );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}
		
		// controllo se il login è stato effettuato correttamente.
		return strpos( $r->body, $username );
	}

	// --------------------------------------------------------------------

	public function logout()
	{
		$post_data = array
		(
			'logout'	=> 1
		);

		$r = $this->request->post( 'http://www.megaupload.com', $post_data );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}
		
		@unlink( $this->cookie_file );
	}

	// --------------------------------------------------------------------
	
	public function generate( $link )
	{
		// Convalido l'indirizzo.
		if ( ! $this->is_valid( $link ) )
		{
			return 'invalid-id';
		}
		
		// Rimuovo il segui perchè sennò mi scarica il file.
		$this->request->follow_redirects = false;

		$r = $this->request->get( $link );

		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}

		if ( strstr( $r->body, 'passwordform' ) )
		{
			return 'password-protected';
		}

		if ( $r->headers['Status-Code'] == 302 )
		{
			return $r->headers['location'];
		}
		
		if ( strstr( $r->body, 'downloadlink' ) )
		{
			$html = str_get_html( $r->body );
			return $html->find( '#downloadlink', 0 )->find( 'a', 0 )->href;
		}
		
		return 'link-error';
	}
	
	/**
	 * Send Cookie to Megaupload Cookies Manager
	 *
	 * @return void
	 * @author Namaless
	 **/
	public function fetchCookie( $domain, $cookiename1, $cookievalue1 )
	{
		$post_data = array
		(
			'domain'		=> $domain,
			'cookiename1'	=> $cookiename1,
			'cookievalue1'	=> $cookievalue1,
			'submit' 		=> TRUE
		);

		$r = $this->curl->post( 'http://www.megaupload.com/multifetch/?c=cookies', $post_data );
		
		return strpos( $r->body, "<b>{$domain}</b>" );
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetch Link to Megaupload Cookies Manager
	 *
	 * @return void
	 * @author Namaless
	 **/
	public static function fetchLink( $link, $options )
	{
		$post_data = array
		(
			'srcurl'			=> $link,
			'description'		=> $options['description'],
			'fromemail'			=> $options['fromemail'],
			'toemail'			=> $options['email'],
			'password'			=> $options['password'],
			'multirecipient'	=> $options['multirecipient'],
			'submit'			=> TRUE
		);

		$r = $this->curl->post( 'http://www.megaupload.com/multifetch/', $post_data );
	}

	// --------------------------------------------------------------------
	
	/**
	 * Delete Cookie to Megaupload Cookies Manager
	 *
	 * @return void
	 * @author Namaless
	 **/
	public static function deleteCookie( $domain )
	{
		$ch = curl_init('http://www.megaupload.com/multifetch/?c=cookies&delete=' . $domain);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.megaupload.com/multifetch/?c=cookies');
		curl_setopt($ch, CURLOPT_COOKIEFILE, self::$cookie);
		$result = curl_exec($ch);
		$error = curl_errno($ch);
		curl_close($ch);

		// suppongo che se non trovo il dominio, questo sia stato cancellato.
		return ( strpos($result, "<b>{$domain}</b>") ) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Clear Completed Fetched Links to Megaupload Cookies Manager
	 *
	 * @return void
	 * @author Namaless
	 **/
	public static function clearCompleted()
	{
		$post_data = array(
			'clear'		=> TRUE,
			'submit'	=> TRUE
		);

		$ch = curl_init('http://www.megaupload.com/multifetch/?c=status');
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.megaupload.com/multifetch/?c=status');
		curl_setopt($ch, CURLOPT_COOKIEFILE, self::$cookie);
		$result = curl_exec($ch);
		$error = curl_errno($ch);
		curl_close($ch);
		
		// controlla che siano stati cancellati.
		return ( strpos($result, "Clear completed") ) ? FALSE : TRUE;
	}
}
/*
$options = array(
	'description'	=> 'Powered by Namaless',
	'fromemail'		=> 'P2P-Club - Notification Megaupload Links',
	'toemail'		=> 'namaless@gmail.com'
);

$userdata = array(
	'username'	=> 'p2p-links',
	'password'	=> 'NamalessEteroMancato'
);

$rapidshare = array(
	'user'	=> array(
		'5741515-%64%30%74%6B%69%6E%67',
		'6765850-%52%31%63%63%61%72%64%30'
	)
);

$links = array(
	'http://84.19.189.3/info.txt',
	'http://rapidshare.com/files/153752735/pres.part34.rar'	
);

$megaupload_com = new Megaupload_Com();

if ( $megaupload_com->login($userdata['username'], $userdata['password']) )
{
	foreach ( $links AS $link )
	{
		$megaupload_com->fetchCookie('rapidshare.com', 'user', $rapidshare['user'][rand(0,1)]);
		$megaupload_com->fetchLink($link, $options);
	}

	$megaupload_com->clearCompleted();
	
	$megaupload_com->logout();
}
*/
