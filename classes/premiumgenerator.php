<?php

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

define( 'PREMIUMGENERATOR_VERSION', "0.0.2-community" );
	
require dirname( __FILE__ ) . '/hoster.php';
require dirname( __FILE__ ) . '/simple_html_dom.php';

// --------------------------------------------------------------------

/**
 * PremiumGenerator Class
 *
 * This is a startup class of library.
 *
 * @package		PremiumGenerator
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Namaless
 * @version		0.0.2-community
 * @link		http://userguide.premium-generators.com/core
 */
final class PremiumGenerator {

	public static $hosters = array();
	
	// --------------------------------------------------------------------
	
	public static function error( $message, $code = 0 )
	{
		throw new Exception( $message, $code );
	}
	
	// --------------------------------------------------------------------
	
	public static function exception( Exception $e )
	{
		echo "<h1>Exception Error: " . $e->getMessage() . " [" . $e->getCode() . "]</h1>";
		exit;
	}
	
	// --------------------------------------------------------------------
	
	public static function factory( $hoster, array $config = array() )
	{
		if ( ! isset( self::$hosters[$hoster] ) )
		{
			try
			{
				$hoster_class_file = dirname( __FILE__ ) . '/hosters/' . $hoster . '.php';
				
				if ( ! file_exists( $hoster_class_file ) )
				{
					self::error( "Hoster not avariable" );
				}
				
				require_once( $hoster_class_file );
			
				$hoster_class = 'PremiumGenerator_Hoster_' . ucfirst( $hoster );
			
				if ( ! class_exists( $hoster_class ) )
				{
					self::error( "Hoster class not found" );
				}
				
				self::$hosters[$hoster] = new $hoster_class( $config );
			}
			catch ( Exception $e )
			{
				self::exception( $e );
			}
		}
		
		if ( ! self::$hosters[$hoster]->request )
		{
			try
			{
				$request_class_file = dirname( __FILE__ ) . '/requests/' . self::$hosters[$hoster]->request_class . '.php';
			
				if ( ! file_exists( $request_class_file ) )
				{
					self::error( "Request not avariable" );
				}
			
				require_once $request_class_file;
			
				$request_class = 'PremiumGenerator_Request_' . ucfirst( self::$hosters[$hoster]->request_class );
			
				if ( ! class_exists( $request_class ) )
				{
					self::error( "Request class not found" );
				}
			
				self::$hosters[$hoster]->request = new $request_class;
			}
			catch ( Exception $e )
			{
				self::exception( $e );
			}
			
			self::$hosters[$hoster]->initialize( $config );
		}

		return self::$hosters[$hoster];
	}
	
	// --------------------------------------------------------------------
}