<?php

require dirname( __FILE__ ) . '/classes/premiumgenerator.php';

$MU_Config = array(
	'user'	=> 'pippo',
	'pass'	=> 'franco',
	'cookie_file'	=> dirname( __FILE__ ) . '/cooik.php'
);

$link = 'http://www.megaupload.com/?d=634YUARR';

// $MU = PremiumGenerator::instance( 'megaupload' );
$MU = PremiumGenerator::factory( 'megaupload', $MU_Config );

$MV = PremiumGenerator::factory( 'megavideo', $MU_Config );

$HF = PremiumGenerator::factory( 'hotfile' );

print_r( $MU );

//$MU->login( 'pippo', 'franco' );
if ( ! $MU->login() )
{
	// Errore: no-login
}

if ( ! $MU->is_online( $link ) )
{
	// Errore: invalid
}

$premium = $MU->generate( $link );

if ( ! $premium )
{
	// Errore: no-premium
}

echo $premium;

print_r( $MU->info( 'VERSION' ) );