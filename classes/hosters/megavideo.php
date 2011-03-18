<?php if ( ! defined( 'PREMIUMGENERATOR_VERSION' ) ) exit( 'No direct script access allowed' );

class PremiumGenerator_Hoster_Megavideo extends PremiumGenerator_Hoster {

	const VERSION 	= '0.0.1-community';
	const NAME 		= 'Megavideo';
	const URL 		= 'http://www.megavideo.com';
	const DESC 		= '';
	const AUTHOR 			= 'Namaless';
	const AUTHOR_EMAIL 		= 'namaless@gmail.com';
	const AUTHOR_WEBSITE 	= 'http://www.namaless.com/';


	public $request_class = 'curl';
	
	public $cookie_file = 'cookie_mv_data.php';
	public $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1';
	
	private $login_url = 'http://www.megavideo.com/?s=signup';
	private $logout_url = 'http://www.megavideo.com/?logout=1';
	private $player_url = 'http://www.megavideo.com/xml/player_login.php';
	
	private $standard_regex = '/http:\/\/([a-z0-9]+)\.megavideo\.com\/\?v=([A-Z0-9]{8})/i';
	private $premium_regex = '/http:\/\/([a-z0-9]+)\.megavideo\.com\/files\/([a-z0-9]{32})\/(.+)/i';
	private $filename_regex = '';
	
	private $online_error_regex = 'This video has been removed due to infringement.';
	
	private $login_regex = 'Premium';
	private $generate_regex = '|downloadurl="(.+)"|U';
	private $user_id_regex = '|flashvars.userid = "(.+)";|U';
	
	// --------------------------------------------------------------------
	
	protected function _autoload()
	{
		$this->request->cookie_file = $this->cookie_file;
		$this->request->user_agent = $this->user_agent;
	}
	
	// --------------------------------------------------------------------

	public function is_standard( $link )
	{
		preg_match( $this->standard_regex, $link, $result );
		return isset( $result[2] ) ? TRUE : FALSE;
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
		$post_data = array(
			'nickname'	=> ( $username ? $username : $this->user ),
			'password'	=> ( $password ? $password : $this->pass ),
			'action'	=> "login"
		);
		
		$r = $this->request->post( $this->login_url, $post_data );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}

		return strpos( $r->body, $this->login_regex );
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
		$user_id = $this->get_user_id( $link );
		$link_id = $this->get_link_id( $link );

		// Controllo che il link sia valido.
		if ( ! $this->is_standard( $link ) )
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

		preg_match( $this->generate_regex, $r->body, $result );

		return urldecode( $result[1] );
	}
	
	// --------------------------------------------------------------------
	
	private function get_user_id($link)
	{
		$r = $this->request->get($link);
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}
		
		preg_match( $this->user_id_regex, $r->body, $result );
		
		return ( isset( $result[1] ) ) ? $result[1] : false;
	}
	
	// --------------------------------------------------------------------
	
	private function get_link_id($url)
	{
		preg_match( $this->standard_regex, $url, $result );
		return isset( $result[2] ) ? $result[2] : FALSE;
	}
	
	// --------------------------------------------------------------------
}