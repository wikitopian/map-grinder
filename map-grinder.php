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

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class MapGrinder {
    private $dir;
    private $settings;

    public function __construct() {
        $this->dir = plugin_dir_url( __FILE__ );

        register_activation_hook( __FILE__, array( &$this, 'activation' ) );
        register_activation_hook( __FILE__, array( &$this, 'create_google_table' ) );
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
            'label' => 'XXXYYYZZZ',
            'address' => '626 S Gospel St, Paoli, IN 47454',
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
        $label              = $data->label;

        error_log($geometry->location->latitude);

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix.'map_grinder_google',
            array(
                'label' => $label,
                'types' => $types[0],
                'location_type' => $geometry->location_type,

                'street_number_long_name' => $address_components[0]->long_name,
                'street_number_short_name' => $address_components[0]->short_name,

                'route_long_name' => $address_components[1]->long_name,
                'route_short_name' => $address_components[1]->short_name,

                'locality_long_name' => $address_components[2]->long_name,
                'locality_short_name' => $address_components[2]->short_name,

                'administrative_area_level_3_long_name' => $address_components[3]->long_name,
                'administrative_area_level_3_short_name' => $address_components[3]->short_name,

                'administrative_area_level_2_long_name' => $address_components[4]->long_name,
                'administrative_area_level_2_short_name' => $address_components[4]->short_name,

                'administrative_area_level_1_long_name' => $address_components[5]->long_name,
                'administrative_area_level_1_short_name' => $address_components[5]->short_name,

                'country_long_name' => $address_components[6]->long_name,
                'country_short_name' => $address_components[6]->short_name,

                'postal_code_long_name' => $address_components[7]->long_name,
                'postal_code_short_name' => $address_components[7]->short_name,

                'formatted_address' => $formatted_address,

                'latitude' => $geometry->location->latitude,
                'longitude' => $geometry->location->longitude,

                'northeast_latitude' => $geometry->viewport->northeast_latitude,
                'northeast_longitude' => $geometry->viewport->northeast_longitude,

                'southwest_latitude' => $geometry->viewport->southwest_latitude,
                'southwest_longitude' => $geometry->viewport->southwest_longitude
            )
        );


        exit();
    }
    public function create_google_table() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $sql = <<<SQL

CREATE TABLE {$prefix}map_grinder_google (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    label VARCHAR(100),
    types VARCHAR(100),
    location_type VARCHAR(100),
    street_number_long_name VARCHAR(100),
    street_number_short_name VARCHAR(100),
    route_long_name VARCHAR(100),
    route_short_name VARCHAR(100),
    locality_long_name VARCHAR(100),
    locality_short_name VARCHAR(100),
    administrative_area_level_3_long_name VARCHAR(100),
    administrative_area_level_3_short_name VARCHAR(100),
    administrative_area_level_2_long_name VARCHAR(100),
    administrative_area_level_2_short_name VARCHAR(100),
    administrative_area_level_1_long_name VARCHAR(100),
    administrative_area_level_1_short_name VARCHAR(100),
    country_long_name VARCHAR(100),
    country_short_name VARCHAR(100),
    postal_code_long_name VARCHAR(100),
    postal_code_short_name VARCHAR(100),
    formatted_address VARCHAR(250),
    latitude DOUBLE,
    longitude DOUBLE,
    northeast_latitude DOUBLE,
    northeast_longitude DOUBLE,
    southwest_latitude DOUBLE,
    southwest_longitude DOUBLE,
    UNIQUE KEY id (id)
);

SQL;
        dbDelta( $sql );
    }
}

$map_grinder = new MapGrinder();

function map_grinder_uninstall() {
    delete_option( 'map-grinder' );
}

?>
