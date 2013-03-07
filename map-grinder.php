<?php
/*
Plugin Name: Map Grinder
Plugin URI: http://www.github.com/wikitopian/map-grinder
Description: Geocoding console
Version: 1.0
Author: Matt Parrott
Author URI: http://www.swarmstrategies.com/matt
License: GPLv2
 */

class MapGrinder {
    private $dir;
    private $settings;

    public function __construct() {
        $this->dir = plugin_dir_url( __FILE__ );

        register_activation_hook( __FILE__, array( &$this, 'activation' ) );
        register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ) );
        register_uninstall_hook( __FILE__, 'map_grinder_uninstall' );
        add_action( 'admin_init', array( &$this, 'admin_init' ) );
    }
    public function activation() {
        if( !$this->settings = get_option( 'map-grinder' ) ) {
            $this->settings = array();

            // Defaults
            $this->settings['activated'] = true;
            $this->settings['widget_title'] = 'Map Grinder';

            add_option( 'map-grinder', $this->settings );
        }
    }
    public function deactivation() {
    }
    public function admin_init() {
        if( !isset( $this->settings ) ) {
            $this->settings = get_option( 'map-grinder' );
        }
        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts_map_grinder' ) );
        add_action( 'admin_enqueue_style',   array( &$this, 'admin_enqueue_style_map_grinder' ) );
        add_action( 'admin_head', array( &$this, 'admin_head_js' ) );
        add_action( 'wp_dashboard_setup', array( &$this, 'wp_dashboard_setup_add_widget' ) );
    }
    public function admin_enqueue_scripts_map_grinder( $page ) {
        wp_enqueue_script( 'map-grinder' );
    }
    public function admin_enqueue_style_map_grinder() {
        wp_register_style( 'map-grinder', $this->dir.'css/map-grinder.css' );
        wp_enqueue_style(  'map-grinder' );
    }
    public function wp_dashboard_setup_add_widget() {
        wp_add_dashboard_widget(
            'map-grinder_widget',
            $this->settings['widget_title'],
            array( &$this, 'widget' )
        );
    }
    public function widget() {
        echo <<<HTML

Map Grinder

HTML;
    }
    public function admin_head_js() {
        wp_register_script( 'map-grinder', $this->dir.'js/map-grinder.js' );
        wp_enqueue_script(  'map-grinder' );
    }
}

$map_grinder = new MapGrinder();

function map_grinder_uninstall() {
    delete_option( 'map-grinder' );
}

?>
