<?php

require dirname( __FILE__ ) . '/classes/premiumgenerator.php';

// --------------------------------------------------------------------

$mu_config = array(
	'user'			=> '',
	'pass'			=> '',
	'cookie_file'	=> dirname( __FILE__ ) . '/cookie_mu_data.php'
);

// --------------------------------------------------------------------

$mv_user = '';
$mv_pass = '';

$hf_user = '';
$hf_pass = '';

$fs_user = '';
$fs_pass = '';

$fo_user = '';
$fo_pass = '';

$ul_user = '';
$ul_pass = '';

$vbb_user = '';
$vbb_pass = '';

// --------------------------------------------------------------------

if ( file_exists( dirname( __FILE__ ) . '/pro-config.php' ) )
{
	require_once( dirname( __FILE__ ) . '/pro-config.php' );
}

// --------------------------------------------------------------------

$mu_link = 'http://www.megaupload.com/?d=634YUARR';
$mv_link = 'http://megavideo.com/?v=6UQWSEPJ';
$hf_link = 'http://hotfile.com/dl/56151286/6a7ff6f/cokula.woothemes.canvas.zip.html';
$fs_link = 'http://www.fileserve.com/file/y7r9TH2/cins3ep01';
$fo_link = '';
$ul_link = '';
$vbb_link = 'http://videobb.com/video/VnheyW2xautk';

// --------------------------------------------------------------------

$MU = PremiumGenerator::factory( 'megaupload', $mu_config );
$MV = PremiumGenerator::factory( 'megavideo', array( 'cookie_file'	=> dirname( __FILE__ ) . '/cookie_mv_data.php' ) );
$HF = PremiumGenerator::factory( 'hotfile', array( 'cookie_file'	=> dirname( __FILE__ ) . '/cookie_hf_data.php' ) );
$FS = PremiumGenerator::factory( 'fileserve', array( 'cookie_file'	=> dirname( __FILE__ ) . '/cookie_fs_data.php' ) );
$FO = PremiumGenerator::factory( 'filesonic', array( 'cookie_file'	=> dirname( __FILE__ ) . '/cookie_fo_data.php' ) );
$UL = PremiumGenerator::factory( 'uploading', array( 'cookie_file'	=> dirname( __FILE__ ) . '/cookie_ul_data.php' ) );
$VBB = PremiumGenerator::factory( 'videobb', array( 'cookie_file'	=> dirname( __FILE__ ) . '/cookie_vbb_data.php' ) );

// --------------------------------------------------------------------

// --------------------------------------------------------------------
// 							MegaUpload
// --------------------------------------------------------------------

$mu_onoff = true;

if ( $mu_onoff )
{
	if ( ! $MU->login() )
	{
		exit( "Error Login" );
	}
	
	$mu_response = $MU->generate( $mu_link );
	
	switch ( $mu_response )
	{
		case 'no-response':
		break;
		
		case 'link-error':
		break;
		
		default:
			echo $mu_response;
			echo "<hr>";
		break;
	}
}

// --------------------------------------------------------------------
//							MegaVideo
// --------------------------------------------------------------------

$mv_onoff = false;

if ( $mv_onoff )
{
	if ( ! $MV->login( $mv_user, $mv_pass ) )
	{
		exit( "Error Login" );
	}
	
	$mv_response = $MV->generate( $mv_link );
	
	switch ( $mv_response )
	{
		case 'no-response':
		break;
		
		case 'link-error':
		break;
		
		default:
			echo $mv_response;
			echo "<hr>";
		break;
	}
}

// --------------------------------------------------------------------
//							FileServe
// --------------------------------------------------------------------

$fs_onoff = false;

if ( $fs_onoff )
{
	if ( ! $FS->login( $fs_user, $fs_pass ) )
	{
		exit( "Error Login" );
	}
	
	$fs_response = $FS->generate( $fs_link );
	
	switch ( $fs_response )
	{
		case 'no-response':
		break;
		
		case 'link-error':
		break;
		
		default:
			echo $fs_response;
			echo "<hr>";
		break;
	}
}

// --------------------------------------------------------------------
//							HotFile
// --------------------------------------------------------------------

$hf_onoff = false;

if ( $hf_onoff )
{
	if ( ! $HF->login( $hf_user, $hf_pass ) )
	{
		exit( "Error Login" );
	}
	
	$hf_response = $HF->generate( $hf_link );
	
	switch ( $hf_response )
	{
		case 'no-response':
		break;
		
		case 'link-error':
		break;
		
		default:
			echo $hf_response;
			echo "<hr>";
		break;
	}
}

// --------------------------------------------------------------------
//							VideoBB
// --------------------------------------------------------------------

$vbb_onoff = false;

if ( $vbb_onoff )
{
	if ( ! $VBB->login( $vbb_user, $vbb_pass ) )
	{
		exit( "Error Login" );
	}
	
	$vbb_response = $VBB->generate( $vbb_link );
	
	switch ( $vbb_response )
	{
		case 'no-response':
		break;
		
		case 'link-error':
		break;
		
		default:
			echo $vbb_response;
			echo "<hr>";
		break;
	}
}

// --------------------------------------------------------------------

// print_r( $MU->info( 'VERSION' ) );