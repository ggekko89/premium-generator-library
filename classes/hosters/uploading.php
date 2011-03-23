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
 * PremiumGenerator_Hoster_Uploading Class
 *
 * This is hoster class of hosters management.
 *
 * @package		PremiumGenerator
 * @subpackage	Libraries
 * @category	Hoster
 * @author		Namaless
 * @version		0.0.1-community
 * @link		http://userguide.premium-generators.com/hosters/uploading
 */

class PremiumGenerator_Hoster_Uploading extends PremiumGenerator_Hoster {

	const VERSION 	= '0.0.1-community';
	const NAME 		= 'Uploading';
	const URL 		= 'http://uploading.com';
	const DESC 		= '';
	const AUTHOR 			= 'Namaless';
	const AUTHOR_EMAIL 		= 'namaless@gmail.com';
	const AUTHOR_WEBSITE 	= 'http://www.namaless.com/';

	// --------------------------------------------------------------------

	public $request_class = 'curl';
	
	public $referer = 'http://uploading.com';
	public $cookie_file = 'cookie_ul_data.php';
	public $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
	
	private $login_url = 'http://uploading.com/general/login_form/';
	private $logout_url = 'http://uploading.com/signout/';
	
	private $standard_regex = '/http:\/\/uploading\.com\/files\/([a-z0-9]{8})\/(.+)\//i';
//	private $premium_regex = '/http:\/\/s([a-z0-9]+)\.sharingmatrix\.com\/download\/([a-z0-9]{32})\/([0-9]+)/i';
	private $filename_regex = '';
	
	private $generate_regex = '/file_id: ([0-9]+), code: "([a-z0-9]+)"/i';
	private $generate_url = 'http://uploading.com/files/get/';
	
	private $online_error_regex = 'has been removed due to infringement';
	
	private $login_regex = 'Membership: Premium';
	
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
		preg_match( $this->standard_regex, $link, $result);
		return count( $result ) ? TRUE : FALSE;
	}
	
	// --------------------------------------------------------------------
	
	public function is_premium( $link )
	{
		
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
	
	public function login( $email = '', $password = '' )
	{
		$post_data = array
		(
			'email'		=> ( $email ? $email : $this->user ),
			'password'	=> ( $password ? $password : $this->pass )
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
		
		$this->request->referer = $link;
		
		$r = $this->request->get( $link );
		
		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}

		if ( $r->headers['Status-Code'] != 200 )
		{
			return 'link-error';
		}


		// do_request('files', 'get', {action: 'get_link', file_id: 11738392, code: "b927ec26", pass: $('!pass').value}, r);

		preg_match( $this->generate_regex, $r->body, $result );

		// Controllo di aver catturato i 2 campi.
		if ( ! isset( $result[1] ) OR ! isset( $result[2] ) )
		{
			die( "Errore con la cattura dei 2 campi." );
		}

		// action=get_link&file_id=11738392&code=b927ec26&pass=undefined
		$post_data = array
		(
			'action'	=> "get_link",
			'file_id'	=> "{$result[1]}",
			'code'		=> "{$result[2]}",
			'pass'		=> "undefined"
		);

		$tid = str_replace( ".", "12", microtime( true ) );
		
		$this->request->referer = $link;
		
		$r = $this->request->post( "{$this->generate_url}?JsHttpRequest={$tid}-xml", $post_data );

		if ( ! isset( $r->body ) )
		{
			return 'no-response';
		}
		
		$result = json_decode( $r->body );


		return urldecode( $result->js->answer->link );
	}
	
	// --------------------------------------------------------------------
}
