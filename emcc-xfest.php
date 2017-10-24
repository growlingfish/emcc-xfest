<?php
/*
Plugin Name: 		EMCC xFest
Plugin URI:			https://github.com/growlingfish/emcc-xfest
GitHub Plugin URI: 	https://github.com/growlingfish/emcc-xfest
Description: 		EMCC xFest server
Version:     		0.0.0.1
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

add_action( 'rest_api_init', 'xfest_register_v2_api_hooks' );
function xfest_register_v2_api_hooks () {
	/*global $namespace;
	register_rest_route( $namespace.'v2', '/event/(?P<id>\d+)/(?P<user>.+)', array(
		'methods'  => 'GET',
		'callback' => 'xfest_get_event',
		'args' => array(
			'id' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				},
				'required' => true
			),
			'user' => array(
				'validate_callback' => function ($param, $request, $key) {
					$super_admins = get_users(array( 'role' => 'administrator' ));
					foreach ($super_admins as $admin) {
						if (password_verify($admin->user_login, $param)) {
							return true;
						}
					}
					return false;
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'v2', '/events/(?P<user>.+)', array(
		'methods'  => 'GET',
		'callback' => 'xfest_get_events',
		'args' => array(
			'user' => array(
				'validate_callback' => function ($param, $request, $key) {
					$super_admins = get_users(array( 'role' => 'administrator' ));
					foreach ($super_admins as $admin) {
						if (password_verify($admin->user_login, $param)) {
							return true;
						}
					}
					return false;
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'v2', '/messages/(?P<eventid>\d+)/(?P<user>.+)', array(
		'methods'  => 'GET',
		'callback' => 'xfest_get_messages',
		'args' => array(
			'eventid' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				},
				'required' => true
			),
			'user' => array(
				'validate_callback' => function ($param, $request, $key) {
					$super_admins = get_users(array( 'role' => 'administrator' ));
					foreach ($super_admins as $admin) {
						if (password_verify($admin->user_login, $param)) {
							return true;
						}
					}
					return false;
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'v2', '/checkin/geo/(?P<event>\d+)/(?P<duuid>[\w\-]+)/(?P<lat>[\-0-9\.]+)/(?P<lon>[\-0-9\.]+)/(?P<acc>[0-9\.]+)/(?P<user>.+)', array(
		'methods'  => 'GET',
		'callback' => 'xfest_geo_checkin',
		'args' => array(
			'event' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				},
				'required' => true
			),
			'lat' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				},
				'required' => true
			),
			'lon' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				},
				'required' => true
			),
			'acc' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				},
				'required' => true
			),
			'user' => array(
				'validate_callback' => function ($param, $request, $key) {
					$super_admins = get_users(array( 'role' => 'administrator' ));
					foreach ($super_admins as $admin) {
						if (password_verify($admin->user_login, $param)) {
							return true;
						}
					}
					return false;
				},
				'required' => true
			)
		)
	) );
	register_rest_route( $namespace.'v2', '/checkin/beacon/(?P<event>\d+)/(?P<duuid>[\w\-]+)/(?P<uuid>[0-9a-fA-F:\-]+)/(?P<major>\d+)/(?P<minor>\d+)/(?P<user>.+)', array(
		'methods'  => 'GET',
		'callback' => 'xfest_beacon_checkin',
		'args' => array(
			'event' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				},
				'required' => true
			),
			'major' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				},
				'required' => true
			),
			'minor' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				},
				'required' => true
			),
			'user' => array(
				'validate_callback' => function ($param, $request, $key) {
					$super_admins = get_users(array( 'role' => 'administrator' ));
					foreach ($super_admins as $admin) {
						if (password_verify($admin->user_login, $param)) {
							return true;
						}
					}
					return false;
				},
				'required' => true
			)
		)
	) );*/
}
/*
function xfest_get_event ( WP_REST_Request $request ) {
	$event_id = $request['id'];
	
	$return->schedule = array();
        
	$return->beacons = array();
	$beacons = get_terms( array(
    	'taxonomy' => 'beacon',
    	'hide_empty' => false,
	) );
	foreach ($beacons as $beacon) {
			$return->beacons[] = array(
					'ID'                    => $beacon->term_id,
					'UUID'                  => $beacon->name,
					'description'   		=> $beacon->description
			);
	}
	
	$return->buildings = array();
	$query = array(
			'numberposts'   => -1,
			'post_type'     => 'building',
			'post_status'   => 'publish'
	);
	$all_buildings = get_posts( $query );
	foreach ($all_buildings as $building) {
		$this_building = array(
			'ID'            => $building->ID,
			'name'          => $building->post_title,
			'lat'           => get_field( 'latitude', $building->ID ),
			'lon'           => get_field( 'longitude', $building->ID ),
			'beacons'		=> array()
		);
		
		$building_beacons = wp_get_post_terms( $building->ID, 'beacon' );
		foreach ($building_beacons as $beacon) {
				$this_building['beacons'][] = $beacon->term_id;
		}
		
		$return->buildings[] = $this_building;
	}
		
	$return->tracks = array();
	$tags = get_tags();
	foreach ($tags as $tag) {
		if (strtolower($tag->name) == 'subject' || strtolower($tag->name) == 'general') {
			$openday_category = strtolower($tag->name);
		} else {
			$openday_category = false;
		}
		$return->tracks[] = array(
				'ID'                    => $tag->term_id,
				'name'                  => $tag->name,
				'openday_category'		=> $openday_category
		);
	}

	$query = array(
			'numberposts'   => -1,
			'post_type'     => 'activity',
			'post_status'   => 'publish',
			'meta_key'  	=> 'start_time',
			'orderby'       => array('meta_value' => 'ASC'),
			'tax_query'     => array(
					array(
							'taxonomy' => 'events',
							'terms'    => $event_id
					)
			)
	);
	$all_posts = get_posts( $query );

	$dates = array();
	foreach ( $all_posts as $post ) {
			if (!in_array (get_field( 'active_date', $post->ID ), $dates)) {
					$dates[] = get_field( 'active_date', $post->ID );
					$day->date = get_field( 'active_date', $post->ID );
					$day->groups = array();
					$return->schedule[] = $day;
					unset($day);
			}
	}
	
	$dropInLabel = 'Drop-in any time';
	$return->dropInLabel = $dropInLabel;
	
	foreach ( $all_posts as $post ) {       
		$session = array(
				'ID'            => $post->ID,
				'name'          => $post->post_title,
				'image'         => get_the_post_thumbnail($post->ID),
				'location'      => get_field( 'location', $post->ID ),
				'description'   => $post->post_content,
				'openday_category'	=> false,
				'excerpt'       => $post->post_excerpt,
				'accessibility' => get_field( 'accessibility', $post->ID ),
				'speakerNames'  => get_field( 'organiser', $post->ID ),
				'timeStart'     => get_field( 'start_time', $post->ID ),
				'timeEnd'       => get_field( 'end_time', $post->ID ),
				'tracks'        => array(),
				'reward'        => get_field( 'reward', $post->ID ),
				'lat'           => get_field( 'latitude', $post->ID ),
				'lon'           => get_field( 'longitude', $post->ID ),
				'zone'			=> get_field( 'zone', $post->ID ),
				'beacons'		=> array()
		);
		
		$foundZone = false;
		foreach ($return->tracks as $track) {
			if ($track['name'] == 'Zone '.get_field( 'zone', $post->ID )) {
				$foundZone = true;
				$session['tracks'][] = $track['ID'];
				break;
			}
		}
		if (!$foundZone) {
			$trackID = intval(get_field( 'zone', $post->ID ));
			$return->tracks[] = array(
					'ID'                    => $trackID,
					'name'                  => 'Zone '.get_field( 'zone', $post->ID )
			);
			$session['tracks'][] = $trackID;
		}
		
		$tags = wp_get_post_tags( $post->ID );
		foreach ($tags as $tag) {
			if (strtolower($tag->name) == 'subject' || strtolower($tag->name) == 'general') {
				$session['openday_category'] = strtolower($tag->name);
			}
			$session['tracks'][] = $tag->term_id;			
		}
		
		$beacons = wp_get_post_terms( $post->ID, 'beacon' );
		foreach ($beacons as $beacon) {
				$session['beacons'][] = $beacon->term_id;
		}
		
		foreach($return->schedule as $d => $day) {
			if ($day->date == get_field( 'active_date', $post->ID )) {
				$found_time = false;
				foreach ($return->schedule[$d]->groups as $t => $time) {
					if (strlen($session['timeStart']) > 0) { // Activity is scheduled
						if ($time->time == substr($session['timeStart'], 0, -2).'00') {
							$return->schedule[$d]->groups[$t]->sessions[] = $session;
							$found_time = true;
							break;
						}
					} else if ($time->time == $dropInLabel) { // Activity has no specific time
						$return->schedule[$d]->groups[$t]->sessions[] = $session;
						$found_time = true;
						break;			
					}
				}
				if (!$found_time) {
					if (strlen($session['timeStart']) > 0) { // Activity is scheduled
						$group->time = substr($session['timeStart'], 0, -2).'00';
					} else {
						$group->time = $dropInLabel;
					}
					$group->sessions = array($session);
					$return->schedule[$d]->groups[] = $group; 
					unset($group);
				}
				break; 
			}
		}

		unset($session);
	}
	
	foreach ($return->schedule as $d => &$day) {
		usort($day->groups, function ($a, $b) {
			if (strval($a->time) == strval($b->time)) {
				return 0;
			}
			return (strval($a->time) < strval($b->time)) ? -1 : 1;
		});
	}
	
	foreach ($return->schedule as $d => &$day) {
		foreach ($day->groups as $s => &$sessions) { // Convert start and end time strings to UNIX timestamps
			if (strlen ($sessions->time) > 0 && $sessions->time != $dropInLabel) {
				$sessions->time = timeToUTC($sessions->time, $day->date);
			}
			foreach ($sessions->sessions as &$session) {
				if (strlen($session['timeStart']) > 0) {
					$session['timeStart'] = timeToUTC($session['timeStart'], $day->date);
				}
				if (strlen($session['timeEnd']) > 0) {
					$session['timeEnd'] = timeToUTC($session['timeEnd'], $day->date);
				}
			}
		}
		if (strlen($day->date) > 0) { // Convert date strings to UNIX timestamps
			$day->date = timeToUTC('0000', $day->date);     
        }
	}
	
	$return->testing = array(
		'subject' => array(),
		'general' => array()
	);
	foreach ($return->schedule as $d => &$day) {
		foreach ($day->groups as $s => &$sessions) {
			foreach ($sessions->sessions as &$session) {
				if ($session['openday_category']) {
					if ($session['openday_category'] == 'subject') {
						foreach ($session['tracks'] as $track) {
							$return->testing['subject'][] = $track; 
						}
						$return->testing['subject'] = array_values(array_unique($return->testing['subject']));
					} else {
						foreach ($session['tracks'] as $track) {
							$return->testing['general'][] = $track; 
						}
						$return->testing['general'] = array_values(array_unique($return->testing['general'])); 
					}
				}
			}
		}
	}
	foreach ($return->tracks as &$track) {
		if (isset($track['openday_category'])) {
			foreach ($return->testing['subject'] as $subject) {
				if ($track['ID'] == $subject) {
					$track['openday_category'] = 'subject';
				}
			}
			foreach ($return->testing['general'] as $general) {
				if ($track['ID'] == $general) {
					$track['openday_category'] = 'general';
				}
			}
		}
	}
	unset($return->testing);
	
	$response = new WP_REST_Response( $return );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	return $response;
}

function timeToUTC ($time, $day) {
	if (strlen ($time) == 4) {
	    $timestamp = date_create_from_format('Gi Ymd', $time.' '.$day, new DateTimeZone('Europe/London'));
    	$timestamp->setTimezone(new DateTimeZone('UTC'));
	    return $timestamp->getTimestamp();
	} else {
		return time();
	}
}

function xfest_get_events () {
	global $namespace;
	$terms = get_terms( array(
    	'taxonomy' => 'events',
    	'hide_empty' => true,
	) );
	$return    = array();
	foreach ( $terms as $term ) {
		$return[] = array(
			'ID'        	=> $term->term_id,
			'name'     		=> $term->name,
			'description'	=> $term->description,
			'data_route'	=> $namespace.'/event/'.$term->term_id
		);
	}
	
	$response = new WP_REST_Response( $return );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}

function xfest_get_messages ( $request ) {
	$event_id = $request['eventid'];
	
	$query = array(
			'numberposts'   => -1,
			'post_type'     => 'message',
			'post_status'   => 'publish',
			'tax_query'     => array(
					array(
							'taxonomy' => 'events',
							'terms'    => $event_id
					)
			),
			'date_query' => array(
				array(
					'after' => '10 minutes ago'
				)
			)
	);
	$messages = get_posts( $query );
	$return    = array();
	foreach ( $messages as $message ) {
		$return[] = array(
			'ID'        	=> $message->ID,
			'title'     	=> $message->post_title,
			'message'		=> $message->post_content
		);
	}
	
	$response = new WP_REST_Response( $return );
	$response->set_status( 200 );
	$response->header( 'Access-Control-Allow-Origin', '*' );
	
	return $response;
}
*/

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
				center: {lat: 52.938597, lng: -1.195291},
				zoom: 15
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
