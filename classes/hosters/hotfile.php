<?php if ( ! defined( 'PREMIUMGENERATOR_VERSION' ) ) exit( 'No direct script access allowed' );

class PremiumGenerator_Hoster_Hotfile extends PremiumGenerator_Hoster {

	const VERSION 	= '0.0.1-community';
	const NAME 		= 'HotFile';
	const URL 		= 'http://hotfile.com';
	const DESC 		= '';
	const AUTHOR 			= 'Namaless';
	const AUTHOR_EMAIL 		= 'namaless@gmail.com';
	const AUTHOR_WEBSITE 	= 'http://www.namaless.com/';


	public $request_class = 'curl';
	
	public $referer = 'http://hotfile.com';
	public $cookie_file = "cookie_hf_data.php";
	public $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
	
	private $login_url = 'http://hotfile.com/login.php';
	private $logout_url = 'http://hotfile.com/logout.php';

	private $standard_regex = '/http:\/\/(|[a-z0-9.]+)hotfile\.com\/dl\/([0-9]{8})\/([a-z0-9]{7})\/(.+)/i';
	private $premium_regex = '/http:\/\/(|[a-z0-9.]+)hotfile\.com\/get\/([a-z0-9]{40})\/([a-z0-9]{8})\/([0-9]{1})\/([a-z0-9]{16})\/([a-z0-9]{7})\/([0-9]{7})\/(.+)/i';
	private $filename_regex = '';
	
	private $online_error_regex = 'has been removed due to infringement';
	
	private $login_regex = '<span>Premium</span>';
	
	// --------------------------------------------------------------------

	protected function _autoload()
	{
		$this->request->referer = $this->referer;
		$this->request->user_agent = $this->user_agent;
		$this->request->cookie_file = $this->cookie_file;
	}
	
	// --------------------------------------------------------------------
	
	public function is_standard( $link )
	{
		preg_match( $this->standard_regex, $link, $result );
		return count( $result ) ? TRUE : FALSE;
	}
	
	// --------------------------------------------------------------------
	
	public function is_premium( $link )
	{
		preg_match( $this->premium_regex, $link, $result );
		return count( $result ) ? TRUE : FALSE;
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
		$post_data = array(
			'returnto'	=> '/',
			'lang'		=> 'en',
			'user'		=> ( $username ? $username : $this->user ),
			'pass'		=> ( $password ? $password : $this->pass )
		);
		
		$r = $this->request->post( $this->login_url, $post_data );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}
				
		return strstr( $r->body, $this->login_regex );
	}
	
	// --------------------------------------------------------------------

	public function logout()
	{
		$r = $this->request->get( $this->logout_url );
		
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
		
		//print_r( $r ); exit;
		
		if ( ! isset( $r->body ) OR $r->body == 'failed' )
		{
			return 'no-response';
		}
		
		if ( $r->headers['Status-Code'] == 302 )
		{
			return $r->headers['Location'];
		}
		
		if ( $r->headers['Status-Code'] == 200 )
		{
			$html = str_get_html( $r->body );
			return $html->find( 'a[class=click_download]', 0 )->href;
		}
	}
	
	// --------------------------------------------------------------------
}