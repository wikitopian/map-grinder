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
            $this->settings['google_key'] = '';

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
        add_action( 'wp_dashboard_setup',    array( &$this, 'wp_dashboard_setup_add_widget' ) );
        add_action( 'wp_ajax_fetch_geo',     array( &$this, 'fetch_geo' ) );
        add_action( 'wp_ajax_put_geo',       array( &$this, 'put_geo'   ) );
    }
    public function admin_enqueue_scripts_map_grinder( $page ) {
        wp_enqueue_script( 'json2' );

        wp_register_script( 'map-grinder', $this->dir.'js/map-grinder.js' );
        wp_enqueue_script(  'map-grinder' );

        if( isset( $this->settings['google_key'] ) ) {
            wp_register_script( 'google-maps-api', "https://maps.googleapis.com/maps/api/js?key={$this->settings['google_key']}&sensor=false" );
            wp_enqueue_script( 'google-maps-api' );

            wp_register_script( 'google-maps', $this->dir.'js/google-maps.js' );
            wp_enqueue_script(  'google-maps' );
        }
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
        if( isset( $_POST['google_key'] ) ) {
            $this->settings['google_key'] = $_POST['google_key'];
            update_option( 'map-grinder', $this->settings );
        }
        echo <<<HTML

<form method="post">
    <input type="text" name="google_key" value="{$this->settings['google_key']}" size="20" />
    <input type="submit" value="Change Key" />
    <input type="button" value="Grind Map!" id="map-grinder-button" />
</form>


<div id="map_canvas" style="width:100%; height: 200px; background-color: yellow;"></div>

HTML;
    }
    public function fetch_geo() {
        $geo = array(
            'id' => 1,
            'street' => '626 S Gospel St',
            'city' => 'Paoli',
            'county' => 'Orange',
            'state' => 'IN',
            'zip' => '47454'
        );

        $response = array(
            'what' => 'fetch_geo',
            'action' => 'fetch_geo',
            'id' => true,
            'data' => json_encode( $geo )
        );

        $ajax = new WP_Ajax_Response( $response );

        header( 'Content-Type: application/json' );
        $ajax->send();

        exit();
    }
    public function put_geo() {
        $data = $_POST['data'];
        $data = stripslashes($data);
        $data = json_decode($data);
        $data = $data[0];
        $address_components = $data->address_components;
        $formatted_address  = $data->formatted_address;
        $geometry           = $data->geometry;
        $types              = $data->types;

        $address = array();

        $address['street_number-long_name'] = $address_components[0]->long_name;
        $address['street_number-short_name'] = $address_components[0]->short_name;

        $address['route-long_name'] = $address_components[1]->long_name;
        $address['route-short_name'] = $address_components[1]->short_name;

        $address['locality-long_name'] = $address_components[2]->long_name;
        $address['locality-short_name'] = $address_components[2]->short_name;

        $address['administrative_area_level_3-long_name'] = $address_components[3]->long_name;
        $address['administrative_area_level_3-short_name'] = $address_components[3]->short_name;

        $address['administrative_area_level_2-long_name'] = $address_components[4]->long_name;
        $address['administrative_area_level_2-short_name'] = $address_components[4]->short_name;

        $address['administrative_area_level_1-long_name'] = $address_components[5]->long_name;
        $address['administrative_area_level_1-short_name'] = $address_components[5]->short_name;

        $address['country-long_name'] = $address_components[6]->long_name;
        $address['country-short_name'] = $address_components[6]->short_name;

        $address['postal_code-long_name'] = $address_components[7]->long_name;
        $address['postal_code-short_name'] = $address_components[7]->short_name;

        $address['formatted_address'] = $formatted_address;

        $address['types'] = $types;

        $address['lat'] = $geometry->location->ib;
        $address['lon'] = $geometry->location->jb;

        $address['location_type'] = $geometry->location_type;

        $address['viewport_Z_b'] = $geometry->viewport->Z->b;
        $address['viewport_Z_d'] = $geometry->viewport->Z->d;

        $address['viewport_fa_b'] = $geometry->viewport->Z->b;
        $address['viewport_fa_d'] = $geometry->viewport->Z->d;

        update_option( 'map-grinder-temp', $address );
        exit();
    }
}

$map_grinder = new MapGrinder();

function map_grinder_uninstall() {
    delete_option( 'map-grinder' );
}

?>
