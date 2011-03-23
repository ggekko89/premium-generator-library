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
 * PremiumGenerator_Hoster_Fileserve Class
 *
 * This is hoster class of hosters management.
 *
 * @package		PremiumGenerator
 * @subpackage	Libraries
 * @category	Hoster
 * @author		Namaless
 * @version		0.0.1-community
 * @link		http://userguide.premium-generators.com/hosters/fileserve
 */

class PremiumGenerator_Hoster_Fileserve extends PremiumGenerator_Hoster {

	const VERSION 	= '0.0.1-community';
	const NAME 		= 'FileServe';
	const URL 		= 'http://www.fileserve.com';
	const DESC 		= '';
	const AUTHOR 			= 'Namaless';
	const AUTHOR_EMAIL 		= 'namaless@gmail.com';
	const AUTHOR_WEBSITE 	= 'http://www.namaless.com/';

	// --------------------------------------------------------------------

	public $request_class = 'curl';
	
	public $referer = 'http://www.fileserve.com';
	public $cookie_file = "cookie_fs_data.php";
	public $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
	
	private $login_url = 'http://www.fileserve.com/login.php';
	private $logout_url = 'http://www.fileserve.com/logout.php';
	
	private $standard_regex = '/http:\/\/www\.fileserve\.com\/file\/([a-zA-Z0-9]+)/i';
	private $premium_regex = '/http:\/\/fs([0-9]+)dm\.fileserve\.com\/file\/([a-zA-Z0-9]+)\/([a-z0-9]{32})\/([0-9a-z]{5})\/(.*)+/i';
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
			'loginFormSubmit'	=> TRUE,
			'loginUserName'		=> ( $username ? $username : $this->user ),
			'loginUserPassword'	=> ( $password ? $password : $this->pass )
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
				
		if ( ! isset( $r->headers['Location'] ) OR ! $r->headers['Location'] )
		{
			return 'no-response';
		}
		
		if ( $r->headers['Status-Code'] == 302 )
		{
			return $r->headers['Location'];
		}
		
		return 'no-location';
	}
	
	// --------------------------------------------------------------------
}