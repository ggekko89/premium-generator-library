<?php if ( ! defined( 'PREMIUMGENERATOR_VERSION' ) ) exit( 'No direct script access allowed' );

abstract class PremiumGenerator_Hoster
{
	public $request;
	public $request_class;
	
	public $user;
	public $pass;
	public $cookie_file;
	
	public function initialize( array $config = array() )
	{
		if ( ! empty( $config ) )
		{
			foreach ( $config AS $key => $val )
			{
				$this->{$key} = $val;
			}
		}
		
		if ( method_exists( $this, '_autoload' ) )
		{
			$this->_autoload();
		}
	}

	public function info( $key = '' )
	{
		$infos = array( 'VERSION', 'AUTHOR', 'AUTHOR_EMAIL', 'AUTHOR_WEBSITE', 'NAME', 'URL', 'DESC' );
		
		$result = array();
		
		if ( empty( $key ) )
		{
			foreach ( $infos AS $info )
			{
				@eval( '$result["'.$info.'"] = self::'.$info.';' );
			}
		}
		else
		{
			@eval( '$result = self::'.$key.';' );
		}
		
		return $result;
	}
	
	abstract public function is_standard( $link );
	abstract public function is_premium( $link );
	abstract public function is_online( $link );
	
	abstract public function login();	
	abstract public function logout();
	
	abstract public function generate( $link );
}