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
 * PremiumGenerator_Hoster_FileSonic Class
 *
 * This is hoster class of hosters management.
 *
 * @package		PremiumGenerator
 * @subpackage	Libraries
 * @category	Hoster
 * @author		Namaless
 * @version		0.0.1-community
 * @link		http://userguide.premium-generators.com/hosters/filesonic
 */

class PremiumGenerator_Hoster_FileSonic extends PremiumGenerator_Hoster {

	const VERSION 	= '0.0.1-community';
	const NAME 		= 'FileSonic';
	const URL 		= 'http://www.filesonic.com';
	const DESC 		= '';
	const AUTHOR 			= 'Namaless';
	const AUTHOR_EMAIL 		= 'namaless@gmail.com';
	const AUTHOR_WEBSITE 	= 'http://www.namaless.com/';

	// --------------------------------------------------------------------
	
	public $request_class = 'curl';
	
	public $referer = 'http://filesonic.com';
	public $cookie_file = "cookie_fo_data.php";
	public $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
	
	private $login_url = 'http://filesonic.com/ajax_scripts/login.php';
	private $logout_url = 'http://filesonic.com/logout';
	
	private $standard_regex = '/http:\/\/filesonic\.com\/file\/([0-9]{8})\/(.+)/i';
	private $premium_regex = '/http:\/\/s([a-z0-9]+)\.filesonic\.com\/download\/([a-z0-9]{32})\/([0-9]+)/i';
	private $filename_regex = '';
	
	private $online_error_regex = 'has been removed due to infringement';
	
	private $login_regex = '<h3>Premium';
	
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
		return isset( $result[1] ) ? TRUE : FALSE;
	}
	
	// --------------------------------------------------------------------

	public function is_premium( $link )
	{
		preg_match( $this->premium_regex, $link, $result );
		return isset( $result[2] ) ? TRUE : FALSE;
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
		if ( file_exists( $this->cookie_file ) )
		{
			return true;
		}
		
		$username = ( $username ? $username : $this->user );
		$password = ( $password ? $password : $this->pass );

		$r = $this->request->get( "{$this->login_url}?email={$username}&password={$password}" );
		
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

		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}

		if ( $r->headers['Status-Code'] == 302 )
		{
			return $r->headers['Location'];
		}

		return 'link-error';
	}
	
	// --------------------------------------------------------------------
}