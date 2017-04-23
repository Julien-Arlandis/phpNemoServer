<?php

define('SERVER_VERSION', '0.94.5');
require_once( __DIR__."/lib/class.jntp.php" );
require_once(__DIR__."/lib/class.tools.php");

if( !file_exists( __DIR__ . '/conf/config.json' ) ) { require_once( __DIR__."/install/install.php" ); die(); }
if( file_exists( __DIR__ . '/sleep' ) ) { die( 'You must remove sleep file to continue<br><strong>rm jntp/sleep</strong>' ); }
JNTP::init();
