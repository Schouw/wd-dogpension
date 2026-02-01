<?php
/**
* Plugin Name: Winther Dogspension
* Description: Dog pension
* Version: 1.0.0
* Author: Sabina Winther
* Text Domain: wd-dog-pension
*/

// Prevent direct access

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

// Define global vars
define( 'WT_DOG_PENSION_VERSION', '1.0.0' );
define( 'WT_DOG_PENSION_PATH', plugin_dir_path( __FILE__ ) );
define( 'WT_DOG_PENSION_URL', plugin_dir_url( __FILE__ ) );
define('WT_DOG_PENSION_TEXT_DOMAIN', 'wd-dog-pension');
define('WT_DEBUG', true);

// Load everything
require_once WT_DOG_PENSION_PATH . 'includes/ClassLoader.php';


//Init the database
register_activation_hook( __FILE__, function() {
    WDDP_DatabaseSetup::install();
});

WDDP_SettingsSetup::init();
WDDP_AdminMenu::init();
WDDP_BlockSetup::init();
WDDP_RestSetup::init();
WDDP_WooCommerceSetup::init();
WDDP_EnqueueSetup::init();

if(WT_DEBUG) {
    WDDP_DebugMailSetup::setupDebugEmail();
}

