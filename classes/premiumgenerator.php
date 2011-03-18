<?php

// --community --retail
define( 'PREMIUMGENERATOR_VERSION', "0.0.1-community" );

require dirname( __FILE__ ) . '/hoster.php';
require dirname( __FILE__ ) . '/simple_html_dom.php';

final class PremiumGenerator {

	public static $hosters = array();
	
	public static function factory( $hoster, array $config = array() )
	{
		if ( ! isset( PremiumGenerator::$hosters[$hoster] ) )
		{
			require_once dirname( __FILE__ ) . '/hosters/' . $hoster . '.php';
			
			$hoster_class = 'PremiumGenerator_Hoster_' . ucfirst( $hoster );
			
			PremiumGenerator::$hosters[$hoster] = new $hoster_class( $config );
		}
		
		if ( ! PremiumGenerator::$hosters[$hoster]->request )
		{
			require_once dirname( __FILE__ ) . '/requests/' . PremiumGenerator::$hosters[$hoster]->request_class . '.php';
			
			$request_class = 'PremiumGenerator_Request_' . ucfirst( PremiumGenerator::$hosters[$hoster]->request_class );
			
			PremiumGenerator::$hosters[$hoster]->request = new $request_class;
			
			PremiumGenerator::$hosters[$hoster]->initialize( $config );
		}

		return PremiumGenerator::$hosters[$hoster];
	}
}