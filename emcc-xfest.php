<?php
/*
Plugin Name: 		EMCC xFest
Plugin URI:			https://github.com/growlingfish/emcc-xfest
GitHub Plugin URI: 	https://github.com/growlingfish/emcc-xfest
Description: 		EMCC xFest server
Version:     		0.0.0.3
Author:      		Ben Bedwell
Author URI:  		http://www.growlingfish.com/
License:     		GPL3
License URI: 		https://www.gnu.org/licenses/gpl-3.0.html
*/

// Prevent direct access
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

register_activation_hook( __FILE__, 'xfest_activate' );
function xfest_activate () {
	flush_rewrite_rules();
}
/*
add_action( 'pre_get_posts', 'xfest_meta_key_filter' );
function xfest_meta_key_filter( $query ) { // allow to filter Activities by Event or Zone in the Admin panel
	if ( $query->is_admin && isset( $_GET['zone'] ) && strlen($_GET['zone']) > 0 ) {
		$meta_key_query = array(
			array(
				'key' => 'zone',
				'value' => $_GET['zone'],
			)
		);
		$query->set( 'meta_query', $meta_key_query );
	}

	if ( $query->is_admin && isset( $_GET['event'] ) && strlen($_GET['event']) > 0 ) {
		$tax_query = array(
			array(
				'taxonomy' => 'events',
				'terms' => $_GET['event'],
			)
		);
		$query->set( 'tax_query', $tax_query );
	}  
	return $query;
}

add_action( 'restrict_manage_posts', 'xfest_filter_activities_by_zone' );
function xfest_filter_activities_by_zone () {
    if (isset($_GET['post_type']) && $_GET['post_type'] == 'activity') {
        $values = array(
            'Zone 1' => '1', 
            'Zone 2' => '2',
            'Zone 3' => '3',
            'Zone 4' => '4',
            'Zone 5' => '5'
        );
        ?>
        <select name="zone">
        <option value=""><?php _e('All Zones', 'xfest'); ?></option>
        <?php
            $current_v = isset($_GET['zone'])? $_GET['zone']:'';
            foreach ($values as $label => $value) {
                printf
                    (
                        '<option value="%s"%s>%s</option>',
                        $value,
                        $value == $current_v? ' selected="selected"':'',
                        $label
                    );
                }
        ?>
        </select>
        <?php
    }
}

add_action( 'restrict_manage_posts', 'xfest_filter_activities_by_event' );
function xfest_filter_activities_by_event () {
    if (isset($_GET['post_type']) && $_GET['post_type'] == 'activity') {
    	$events = get_terms( array(
			'taxonomy' => 'events'
		) );
        ?>
        <select name="event">
        <option value=""><?php _e('All Events', 'xfest'); ?></option>
        <?php
            $current_v = isset($_GET['event'])? $_GET['event']:'';
            foreach ($events as $event) {
                printf
                    (
                        '<option value="%s"%s>%s</option>',
                        $event->term_id,
                        $event->term_id == $current_v? ' selected="selected"':'',
                        $event->name
                    );
                }
        ?>
        </select>
        <?php
    }
}
*/

/*
*	Custom API end-points
*/

$namespace = 'xfest/';

add_action( 'rest_api_init', 'xfest_register_api_hooks' );
function xfest_register_api_hooks () {
	global $namespace;
	register_rest_route( $namespace, '/event/(?P<id>\d+)/', array(
		'methods'  => 'GET',
		'callback' => 'xfest_get_event',
		'args' => array(
			'id' => array(
				'validate_callback' => function($param, $request, $key) {
					$term = term_exists($param);
					return is_numeric( $param ) && $term !== 0 && $term !== null;
				},
				'required' => true
			)
		)
	) );
}

function xfest_get_event ( WP_REST_Request $request ) {
	$event_id = $request['id'];

	$return = array(
		'event_id' => $event_id
	);

	$response = new WP_REST_Response( $return );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

/*
*	Geo-meta in Locations custom post types
*/

function xfest_enqueue_admin ($hook) {
	require_once('cred.php');
    wp_enqueue_script( 'google_maps', 'https://maps.googleapis.com/maps/api/js?key='.GOOGLEMAPSAPI.'&libraries=drawing', array( 'jquery' ) );
}
add_action( 'admin_enqueue_scripts', 'xfest_enqueue_admin' );

function adding_custom_meta_boxes_location ( $post ) {
    add_meta_box( 
        'location_geo_meta_box',
        __( 'Geotag' ),
        'render_location_geo_meta_box',
        'location',
        'advanced',
        'high'
    );
}
add_action( 'add_meta_boxes_location', 'adding_custom_meta_boxes_location' );

function render_location_geo_meta_box ( $post ) { ?>
<div id="location_geo_meta_box_map" style="width: 100%; height: 400px;"></div>
<script>	
	jQuery(document).ready(function( $ ) {
		var currentShape;

		// display blank map
		var map = new google.maps.Map(
			document.getElementById('location_geo_meta_box_map'), {
				center: {lat: 52.93909529959011, lng: -1.2034428119659424},
				zoom: 16
			}
		);

		// show drawing manager
		var drawingManager = new google.maps.drawing.DrawingManager({
			drawingMode: google.maps.drawing.OverlayType.MARKER,
			drawingControl: true,
			drawingControlOptions: {
				position: google.maps.ControlPosition.TOP_CENTER,
				drawingModes: [
					google.maps.drawing.OverlayType.MARKER
				]
			},
			markerOptions: {
				clickable: false,
				editable: false
			}
		});
		drawingManager.setMap(map);

		if (jQuery('#acf-field-latitude').val() && jQuery('#acf-field-longitude').val()) {
			currentShape = new google.maps.Marker({
				position: {lat: parseFloat(jQuery('#acf-field-latitude').val()), lng: parseFloat(jQuery('#acf-field-longitude').val())}
			});
			currentShape.setMap(map);
			map.setCenter(currentShape.getPosition());
		}

		google.maps.event.addListener(drawingManager, 'overlaycomplete', function(event) {
			if (typeof currentShape !== "undefined") { // only show one shape at a time
				currentShape.setMap(null);
			}
	
			currentShape = event.overlay;
	
			switch (event.type) {
				case google.maps.drawing.OverlayType.MARKER:
					jQuery('#acf-field-latitude').val(currentShape.getPosition().lat());
					jQuery('#acf-field-longitude').val(currentShape.getPosition().lng());
					break;
			}
		});
	});
</script>
<?php	
}

/*
*	Simplify
*/

add_action('init', 'xfest_remove_categories');
function xfest_remove_categories () {
	register_taxonomy('category', array());
}

add_action('admin_menu', 'xfest_remove_admin_options');
function xfest_remove_admin_options () {
	if (!current_user_can('manage_options')) {
		remove_menu_page( 'edit.php' );
		remove_menu_page( 'edit.php?post_type=page' );
		remove_menu_page( 'edit-comments.php' );
		remove_menu_page( 'tools.php' );
    }
}

add_action('wp_dashboard_setup', 'xfest_remove_dashboard_widgets');
function xfest_remove_dashboard_widgets (){
	if (!current_user_can('manage_options')) {
		remove_meta_box('dashboard_right_now', 'dashboard', 'normal');   // Right Now
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal'); // Recent Comments
		remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');  // Incoming Links
		remove_meta_box('dashboard_plugins', 'dashboard', 'normal');   // Plugins
		remove_meta_box('dashboard_quick_press', 'dashboard', 'side');  // Quick Press
		remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');  // Recent Drafts
		remove_meta_box('dashboard_primary', 'dashboard', 'side');   // WordPress blog
		remove_meta_box('dashboard_secondary', 'dashboard', 'side');   // Other WordPress News
	}
}

?>
