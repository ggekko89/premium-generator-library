<?php if ( ! defined( 'PREMIUMGENERATOR_VERSION' ) ) exit( 'No direct script access allowed' );

/**
 * Premium Generator Library
 *
 * An open source library for generate premium links from File Hosters.
 *
 * @package		PremiumGenerator
 * @author		Namaless
 * @copyright	Copyright (c) 1981 - 2011, Namaless
 * @license		
 * @link		http://www.premium-generators.com
 * @since		Version 0.0.1
 * @filesource
 */

/**
 * PremiumGenerator_Hoster_VideoBB Class
 *
 * This is hoster class of hosters management.
 *
 * @package		PremiumGenerator
 * @subpackage	Libraries
 * @category	Hoster
 * @author		Namaless
 * @version		0.0.1-community
 * @link		http://userguide.premium-generators.com/hosters/videobb
 */

class PremiumGenerator_Hoster_VideoBB extends PremiumGenerator_Hoster {

	const VERSION 	= '0.0.1-community';
	const NAME 		= 'VideoBB';
	const URL 		= 'http://videobb.com';
	const DESC 		= '';
	const AUTHOR 			= 'Namaless';
	const AUTHOR_EMAIL 		= 'namaless@gmail.com';
	const AUTHOR_WEBSITE 	= 'http://www.namaless.com/';
	
	// --------------------------------------------------------------------
	
	public $request_class = 'curl';
	
	public $referer = 'http://videobb.com';
	public $cookie_file = 'cookie_vbb_data.php';
	public $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1';
	
	private $login_url = 'http://videobb.com/login.php';
	private $logout_url = 'http://videobb.com/logout.php';
	private $player_url = 'http://videobb.com/player/player.swf';

	private $standard_regex = '/http:\/\/(|[a-z0-9.]+)videobb\.com\/video\/([a-zA-Z0-9]{12})/i';
	private $premium_regex = '/http:\/\/(|[a-z0-9.]+)megaupload\.com\/files\/([a-z0-9]{32})\/(.+)/i';
	// http://s95.videobb.com/d2?v=VnheyW2xautk&t=1300548181&u=piciolo&c=76cdcb3adbbed3bbcebcd759c6dcc014&d=http%3A//videobb.com/video/VnheyW2xautk
	private $filename_regex = '';
	
	private $online_error_regex = 'The video you have requested is not available';
	
	private $login_regex = '<b class="pre">Premium</b>';
	
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
		$post_data = array
		(
			'login'				=> 'login',
			'login_username'	=> ( $username ? $username : $this->user ),
			'login_password'	=> ( $password ? $password : $this->pass )
		);
		
		if ( file_exists( $this->cookie_file ) )
		{
			return true;
		}

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
		
		
		$video_id = $this->get_video_id( $link );
		$this->request->follow_redirects = TRUE;
		//$r = $this->request->get( "{$this->player_url}?v={$video_id}&em=TRUE&sd=www.videobb.com" );
		$r = $this->request->get( "http://videobb.com/player_control/settings.php?v={$video_id}&fv=v1.1.55" );
		
		print( urldecode( 'checkpromotion=0&data=%5B%7B%22i%22%3A%220e41c5609b9aea50d0b42312400f1e3f%22%2C%22t%22%3A1300555093%2C%22u%22%3A%22piciolo%22%2C%22c%22%3A%220b578aa5728e1c50811a472abb3992f7%22%2C%22v%22%3A%22VnheyW2xautk%22%2C%22ac%22%3A%2234%22%2C%22ut%22%3A%222%22%7D%5D' ) ); exit;
		print_r( json_decode( $r->body ) ); exit;

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
	
	private function get_video_id( $link )
	{
		preg_match( $this->standard_regex, $link, $result );
		return isset( $result[2] ) ? $result[2] : '';
	}
	
	// --------------------------------------------------------------------
}