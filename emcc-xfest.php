<?php
/*
Plugin Name: 		EMCC xFest
Plugin URI:			https://github.com/growlingfish/emcc-xfest
GitHub Plugin URI: 	https://github.com/growlingfish/emcc-xfest
Description: 		EMCC xFest server
Version:     		0.0.1.3
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

add_action('admin_init', 'xfest_init');
function xfest_init () {
	wp_enqueue_script('jquery');
}

add_action('edit_form_advanced', 'xfest_force_post_campus');
function xfest_force_post_campus () {
	echo "<script type='text/javascript'>\n";
	echo "
	jQuery('#publish').click(function() {
		var campuses = jQuery('#campus')
		  .find('.tagchecklist')
		  .find('.ntdelbutton');
		if(campuses.length && campuses.length > 0) {
		} else {
			alert('You have not selected a Campus for this Place. Please add a Campus.');
			setTimeout(\"jQuery('#ajax-loading').css('visibility', 'hidden');\", 100);
			jQuery('#tagsdiv-campus').css('background', '#F96');
			setTimeout(\"jQuery('#publish').removeClass('button-primary-disabled');\", 100);
			return false;
		}
	});
	";
	echo "</script>\n";
}

add_action('edit_form_advanced', 'xfest_force_post_category');
function xfest_force_post_category () {
	echo "<script type='text/javascript'>\n";
	echo "
	jQuery('#publish').click(function() {
		var categories = jQuery('#taxonomy-category')
		  .find('#categorychecklist')
		  .find('.selectit')
		  .find('input');
		if(categories.length && categories.length > 0) {
			var category_selected = false;
			for (var counter = 0; counter < categories.length; counter++) {
				if (categories.get(counter).checked == true) {
					category_selected = true;
					break;
				}
			}
			if (!category_selected) {
				alert('You have not selected a Category for this Place. Please add a Category.');
				setTimeout(\"jQuery('#ajax-loading').css('visibility', 'hidden');\", 100);
				jQuery('#taxonomy-category').find('.tabs-panel').css('background', '#F96');
				setTimeout(\"jQuery('#publish').removeClass('button-primary-disabled');\", 100);
				return false;
			}
		} else {
			alert('You have not selected a Campus for this Place. Please add a Campus.');
			setTimeout(\"jQuery('#ajax-loading').css('visibility', 'hidden');\", 100);
			jQuery('#tagsdiv-campus').css('background', '#F96');
			setTimeout(\"jQuery('#publish').removeClass('button-primary-disabled');\", 100);
			return false;
		}
	});
	";
	echo "</script>\n";
}

add_action( 'pre_get_posts', 'xfest_meta_key_filter' );
function xfest_meta_key_filter( $query ) { // allow to filter Places by Campus in the Admin panel
	if(isset($_GET['campus_admin_filter'])){
		$post_format = sanitize_text_field($_GET['campus_admin_filter']);
		if($post_format != 0){
			$query->query_vars['tax_query'] = array(
				array(
					'taxonomy'  => 'campus',
					'field'     => 'ID',
					'terms'     => array($post_format)
				)
			);

		}
	}
}

add_action( 'restrict_manage_posts', 'xfest_filter_places_by_campus' );
function xfest_filter_places_by_campus () {
    if (isset($_GET['post_type']) && $_GET['post_type'] == 'place') {
        $campus_args = array(
            'show_option_all'   => 'All Campuses',
            'orderby'           => 'NAME',
            'order'             => 'ASC',
            'name'              => 'campus_admin_filter',
            'taxonomy'          => 'campus'
        );

        if(isset($_GET['campus_admin_filter'])){
            $campus_args['selected'] = sanitize_text_field($_GET['campus_admin_filter']);
        }

        wp_dropdown_categories($campus_args);
    }
}

/*
*	Custom API end-points
*/

$namespace = 'xfest/';

add_action( 'rest_api_init', 'xfest_register_api_hooks' );
function xfest_register_api_hooks () {
	global $namespace;
	register_rest_route( $namespace, '/places/', array(
		'methods'  => 'GET',
		'callback' => 'xfest_get_all_places',
		'args' => array()
	) );
	register_rest_route( $namespace, '/messages/', array(
		'methods'  => 'GET',
		'callback' => 'xfest_get_messages',
		'args' => array()
	) );
}

function xfest_get_event ( WP_REST_Request $request ) {
	$event_id = $request['id'];

	$return = get_places_for($event_id);

	$response = new WP_REST_Response( $return );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function xfest_get_all_places ( WP_REST_Request $request ) {
	$return = get_places_for();

	$response = new WP_REST_Response( $return );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function get_places_for ($event_id = null) {
	$result = array();
	
	$campuses = get_terms( array(
    	'taxonomy' => 'campus',
    	'hide_empty' => false,
	) );
	
	foreach ($campuses as $campus) {
		$c = array(
			'campus' => $campus->name,
			'locations' => array()
		);
	
		$locationTypes = get_terms( array(
			'taxonomy' => 'category',
			'hide_empty' => true,
		) );
		foreach ($locationTypes as $type) {
			$location = array(
				'location_type'	=> $type->name,
				'location_id'	=> $type->term_id,
				'places'		=> array()
			);
			
			$args = array(
				'post_type' 		=> 'place',
				'numberposts' 		=> -1,
				'post_status'    	=> 'publish',
				'tax_query' 		=> array(
					array(
						'taxonomy' => 'campus',
						'field' => 'slug',
						'terms' => $campus->slug
					),
					array(
						'taxonomy' => 'category',
						'field' => 'slug',
						'terms' => $type->slug
					)
				)
			);
			if (isset ($event_id) && $event_id != null) {
				$args['tax_query'][] = array(
					'taxonomy' => 'event',
					'field' => 'term_id',
					'terms' => $event_id
				);
			}
			
			$places = get_posts($args);
			foreach ($places as $place) {
				$p = array(
					'id'			=> $place->ID,
					'place'			=> $place->post_title,
					'description'	=> wpautop($place->post_content, false),
					'excerpt'		=> $place->post_excerpt,
					'coords'		=> array(
						'lat'	=> get_field( 'latitude', $place->ID ),
						'lon'	=> get_field( 'longitude', $place->ID )
					),
					'featured_image'	=> get_the_post_thumbnail_url($place->ID, 'thumbnail'),
					'images'			=> array()
				);
				if ($image_1 = get_field( 'image_1', $place->ID )) {
					$p['images'][] = $image_1['sizes']['medium'];
				}
				if ($image_2 = get_field( 'image_2', $place->ID )) {
					$p['images'][] = $image_2['sizes']['medium'];
				}
				if ($image_3 = get_field( 'image_3', $place->ID )) {
					$p['images'][] = $image_3['sizes']['medium'];
				}
				$location['places'][] = $p;
			}
				
			$c['locations'][] = $location;
		}
		
		$result[] = $c;
	}
	
	return $result;
}

function xfest_get_messages () {
	$messages = get_posts( array(
			'numberposts'   => -1,
			'post_type'     => 'message',
			'post_status'   => 'publish',
			'date_query' => array(
				array(
					'after' => '10 minutes ago'
				)
			)
	) );
	$return    = array();
	foreach ( $messages as $message ) {
		$time = get_the_time('U', $message->ID);
		$window = get_field( 'window', $message->ID ) * 60;
		if (time() < ($time + $window)) {
			$return[] = array(
				'id'        	=> $message->ID,
				'message'		=> $message->post_content,
				'remaining'		=> (($time + $window) - time())
			);
		}
	}
	
	$response = new WP_REST_Response( $return );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function xfest_get_messages_for ( WP_REST_Request $request ) {
	$args = array(
			'numberposts'   => -1,
			'post_type'     => 'message',
			'post_status'   => 'publish',
			'tax_query'     => array(
				array(
					'taxonomy' => 'event',
					'terms'    => $request['eventid']
				)
			),
			'date_query' => array(
				array(
					'after' => '10 minutes ago'
				)
			)
	);
	$messages = get_posts( $args );
	$return    = array();
	foreach ( $messages as $message ) {
		$time = get_the_time('U', $message->ID);
		$window = get_field( 'window', $message->ID ) * 60;
		if (time() < ($time + $window)) {
			$return[] = array(
				'id'        	=> $message->ID,
				'message'		=> $message->post_content,
				'remaining'		=> (($time + $window) - time())
			);
		}
	}
	
	$response = new WP_REST_Response( $return );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

/*
*	Geo-meta in places custom post types
*/

function xfest_enqueue_admin ($hook) {
	include_once('cred.php');
    wp_enqueue_script( 'google_maps', 'https://maps.googleapis.com/maps/api/js?key='.GOOGLEMAPSAPI.'&libraries=drawing', array( 'jquery' ) );
}
add_action( 'admin_enqueue_scripts', 'xfest_enqueue_admin' );

function adding_custom_meta_boxes_place ( $post ) {
    add_meta_box( 
        'place_geo_meta_box',
        __( 'Geotag' ),
        'render_place_geo_meta_box',
        'place',
        'advanced',
        'high'
    );
}
add_action( 'add_meta_boxes_place', 'adding_custom_meta_boxes_place' );

function render_place_geo_meta_box ( $post ) { ?>
<div id="place_geo_meta_box_map" style="width: 100%; height: 400px;"></div>
<script>	
	jQuery(document).ready(function( $ ) {
		var currentShape;

		// display blank map
		var map = new google.maps.Map(
			document.getElementById('place_geo_meta_box_map'), {
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
