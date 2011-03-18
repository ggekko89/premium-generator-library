<?php if ( ! defined( 'PREMIUMGENERATOR_VERSION' ) ) exit( 'No direct script access allowed' );

class PremiumGenerator_Hoster_Megaupload extends PremiumGenerator_Hoster {

	const VERSION 	= '0.0.1-community';
	const NAME 		= 'Megaupload';
	const URL 		= 'http://www.megaupload.com';
	const DESC 		= '';
	const AUTHOR 			= 'Namaless';
	const AUTHOR_EMAIL 		= 'namaless@gmail.com';
	const AUTHOR_WEBSITE 	= 'http://www.namaless.com/';
	
	
	public $request_class = 'curl';
	
	public $referer = 'http://www.megaupload.com';
	public $cookie_file = 'cookie_mu_data.php';
	public $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1';
	
	private $login_url = 'http://www.megaupload.com/?c=login';
	private $logout_url = 'http://www.megaupload.com';
	
	private $standard_regex = '/http:\/\/([a-z0-9]+)\.megaupload\.com\/(|[a-z]{2}\/)\?d=([A-Z0-9]{8})/i';
	private $premium_regex = '/http:\/\/([a-z0-9]+)\.megaupload\.com\/files\/([a-z0-9]{32})\/(.+)/i';
	private $filename_regex = '';
	
	private $online_error_regex = 'has been removed due to infringement';
	
	private $login_regex = 'flashvars.status = "premium";';
	
	// --------------------------------------------------------------------
	
	protected function _autoload()
	{
		$this->request->referer = $this->referer;
		$this->request->cookie_file = $this->cookie_file;
		$this->request->user_agent = $this->user_agent;
	}
	
	// --------------------------------------------------------------------

	public function is_standard( $link )
	{
		preg_match( $this->standard_regex, $link, $result );
		return isset( $result[3] ) ? TRUE : FALSE;
	}
	
	// --------------------------------------------------------------------

	public function is_premium( $link )
	{
		preg_match( $this->premium_regex, $link, $result );
		return isset( $result[3] ) ? TRUE : FALSE;
	}
	
	// --------------------------------------------------------------------

	public function is_online( $link )
	{
		$this->request->follow_redirects = FALSE;
		
		$r = $this->request->get( $link );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}
		
		return ( strstr( $this->online_error_regex, $r->body ) ? FALSE : TRUE );
	}
	
	// --------------------------------------------------------------------

	public function login( $username = '', $password = '' )
	{
		$post_data = array
		(
			'login'		=> 1,
			'username'	=> ( $username ? $username : $this->user ),
			'password'	=> ( $password ? $password : $this->pass )
		);
		
		if ( file_exists( $this->cookie_file ) )
		{
			return true;
		}

		$r = $this->request->post( $this->login_url, $post_data );

		//print_r( $this->request );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}
		
		return strpos( $r->body, $this->login_regex );
	}

	// --------------------------------------------------------------------

	public function logout()
	{
		$post_data = array
		(
			'logout'	=> 1
		);

		$r = $this->request->post( $this->logout_url, $post_data );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}
		
		@unlink( $this->cookie_file );
	}

	// --------------------------------------------------------------------
	
	public function generate( $link )
	{
		if ( ! $this->is_standard( $link ) )
		{
			return 'invalid-id';
		}
		
		$this->request->follow_redirects = FALSE;

		$r = $this->request->get( $link );

		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}
		
		//print_r( $r );

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
		
		if ( strstr( $r->body, 'down_ad_butt1' ) )
		{
			$html = str_get_html( $r->body );
			return $html->find( '.down_ad_butt1', 0 )->href;
		}
		
		return 'link-error';
	}
	
	// --------------------------------------------------------------------
}