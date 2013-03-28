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
        register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ) );
        register_uninstall_hook( __FILE__, 'map_grinder_uninstall' );
        add_action( 'admin_init', array( &$this, 'admin_init' ) );
    }
    public function activation() {
        $this->create_input_table();
        $this->create_google_table();

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
        global $wpdb;

        $geo = $wpdb->get_row( "SELECT label, status, address FROM {$wpdb->prefix}map_grinder_input WHERE status = 'READY' LIMIT 1;" );

        $wpdb->query("UPDATE {$wpdb->prefix}map_grinder_input SET status = 'PENDING' WHERE label = '{$geo->label}';");

        if( isset( $geo ) ) {
            $geo = array(
                'label' => $geo->label,
                'status' => $geo->status,
                'address' => $geo->address,
            );
        } else {
            $geo = array(
                'status' => 'EMPTY',
            );
        }

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
        $status             = $data->status;

        $address = array(
            'label' => $label,
            'types' => $types[0],
            'location_type' => $geometry->location_type,
        );

        foreach( $address_components as $a_c_key => $a_c ) {

            if( strcmp( $a_c->types[0], 'street_number' ) == 0 ) {
                $address['street_number_long_name']  = $a_c->long_name;
                $address['street_number_short_name'] = $a_c->short_name;
            }

            if( strcmp( $a_c->types[0], 'route' ) == 0 ) {
                $address['route_long_name']  = $a_c->long_name;
                $address['route_short_name'] = $a_c->short_name;
            }

            if( strcmp( $a_c->types[0], 'locality' ) == 0 ) {
                $address['locality_long_name']  = $a_c->long_name;
                $address['locality_short_name'] = $a_c->short_name;
            }

            if( strcmp( $a_c->types[0], 'administrative_area_level_3' ) == 0 ) {
                $address['administrative_area_level_3_long_name']  = $a_c->long_name;
                $address['administrative_area_level_3_short_name'] = $a_c->short_name;
            }

            if( strcmp( $a_c->types[0], 'administrative_area_level_2' ) == 0 ) {
                $address['administrative_area_level_2_long_name']  = $a_c->long_name;
                $address['administrative_area_level_2_short_name'] = $a_c->short_name;
            }

            if( strcmp( $a_c->types[0], 'administrative_area_level_1' ) == 0 ) {
                $address['administrative_area_level_1_long_name']  = $a_c->long_name;
                $address['administrative_area_level_1_short_name'] = $a_c->short_name;
            }

            if( strcmp( $a_c->types[0], 'country' ) == 0 ) {
                $address['country_long_name']  = $a_c->long_name;
                $address['country_short_name'] = $a_c->short_name;
            }

            if( strcmp( $a_c->types[0], 'postal_code' ) == 0 ) {
                $address['postal_code_long_name']  = $a_c->long_name;
                $address['postal_code_short_name'] = $a_c->short_name;
            }
        }

        $address['formatted_address'] = $formatted_address;

        $address['latitude'] = $geometry->location->latitude;
        $address['longitude'] = $geometry->location->longitude;

        $address['northeast_latitude'] = $geometry->viewport->northeast_latitude;
        $address['northeast_longitude'] = $geometry->viewport->northeast_longitude;

        $address['southwest_latitude'] = $geometry->viewport->southwest_latitude;
        $address['southwest_longitude'] = $geometry->viewport->southwest_longitude;


        global $wpdb;
        $wpdb->insert( $wpdb->prefix.'map_grinder_google', $address );

        $wpdb->query("UPDATE {$wpdb->prefix}map_grinder_input SET status = '{$status}' WHERE label = '{$label}';");


        exit();
    }
    public function create_input_table() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $sql = <<<SQL

CREATE TABLE {$prefix}map_grinder_input (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    label VARCHAR(100),
    status VARCHAR(25),
    address VARCHAR(250),
    UNIQUE KEY id (id)
);

SQL;
        dbDelta( $sql );
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
