<?php
/*
Plugin Name: FlightLog
Plugin URI: http://www.zavedil.com/wordpress-plugin-flightlog
Description: Wordpress plugin to keep track of your flights and to map them nicely.
Version: 1.0.1
Author: Assen Totin
Author URI: http://www.zavedil.com
License: GPL2

    Copyright 2012  Assen Totin  (email : assen.totin@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $flighltog_db_version;
$flightlog_db_version = "1.0";
global $flightlog_measures;
$flightlog_measures = get_option("flightlog_measures");

// Create database
function flightlog_db_create() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = $wpdb->prefix . "flightlog_carriers";
        $sql = "CREATE TABLE $table_name (
                id smallint unsigned NOT NULL AUTO_INCREMENT,
                name varchar(255),
                UNIQUE KEY id (id)
        ) CHARSET=utf8;";
        dbDelta($sql);

        $table_name = $wpdb->prefix . "flightlog_aircrafts";
        $sql = "CREATE TABLE $table_name (
                id smallint unsigned NOT NULL AUTO_INCREMENT,
                name varchar(255),
                UNIQUE KEY id (id)
        ) CHARSET=utf8;";
        dbDelta($sql);

        $table_name = $wpdb->prefix . "flightlog_airports";
        $sql = "CREATE TABLE $table_name (
                id smallint unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255),
                iata varchar(16),
		lat decimal(10,7),
		lng decimal(10,7),
                UNIQUE KEY id (id)
        ) CHARSET=utf8;";
        dbDelta($sql);

	$table_name = $wpdb->prefix . "flightlog_flights";
	$sql = "CREATE TABLE $table_name (
		id int unsigned NOT NULL AUTO_INCREMENT,
		user smallint unsigned,
		dt date,
		airport_from smallint unsigned,
		airport_to smallint unsigned,
		carrier smallint unsigned,
		aircraft smallint unsigned,
		distance smallint unsigned,
		duration decimal(3,1),
		UNIQUE KEY id (id),
		KEY user (user),
		KEY airport_from (airport_from),
		KEY airport_to (airport_to),
		KEY carrier (carrier)
	) CHARSET=utf8;";
	dbDelta($sql);

	add_option("flightlog_db_version", $flightlog_db_version);
	add_option("flightlog_measures", "Metric");
}


function flightlog_admin_menu() {
	add_options_page('FlightLog Settings', 'FlightLog', 'manage_options', 'flightlog-settings-menu', 'flightlog_settings');
	add_management_page('FlightLog Entries', 'FlightLog', 'publish_pages', 'flightlog-entries-menu', 'flightlog_entries');
}


function flightlog_settings() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<h3>FlightLog Settings</h3>';
	flighlog_settings_sys();	
	echo '<hr width=100%>';
	echo '<table width=100%><tr><td valign=top>';
	flighlog_settings_section("carriers");
	echo '</td><td bgcolor=#DDDDDD valign=top>';
	flighlog_settings_section("aircrafts");
	echo '</td><td valign=top>';
	flighlog_settings_section("airports");
	echo '</td></tr></table>';
}


function flighlog_settings_sys() {
	global $wpdb;
	global $flightlog_measures;

	// Update
        if (isset($_POST['measures']) && ($_POST['measures'] != $flightlog_measures)) {
                update_option("flightlog_measures", $_POST['measures']);
		$flightlog_measures = get_option("flightlog_measures");
                // Recalc distances if measures changed
                $results = $wpdb->get_results('SELECT ff.id, fa1.lat AS lat1, fa1.lng AS lng1, fa2.lat AS lat2, fa2.lng AS lng2 FROM ' . $wpdb->prefix . 'flightlog_flights ff JOIN '.$wpdb->prefix.'flightlog_airports fa1 ON fa1.id=ff.airport_from JOIN '.$wpdb->prefix.'flightlog_airports fa2 ON fa2.id=ff.airport_to');
                if ($results) {
                        foreach ($results as $result) {
                                $dist = flightlog_calc_dist($result->lat1, $result->lng1, $result->lat2, $result->lng2);
                                $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "flightlog_flights SET distance=%u WHERE id=%u", $dist, $result->id));
                        }
                }
        }

	// Display all settings
	echo "Current measures: <a href=# onClick=\"javascript:fl_overlay_add_sys('" . $flightlog_measures . "')\">" . $flightlog_measures . "</a>";
}


function flighlog_settings_section($section) {
	global $wpdb;

	echo '<script language=javascript src=/wp-content/plugins/flightlog/flightlog.js></script>';
	echo '<link type=text/css rel=stylesheet href=/wp-content/plugins/flightlog/flightlog.css>';

	// Insert new record, if submitted
	echo '<script language=javascript>alert('.$_POST["section"].');</script>';
	if(isset($_POST["section"]) && $_POST["section"] == $section && $_POST["Submit"] == "Add") {
		if ($section == "airports") {
			$_POST["iata"] = strtoupper($_POST["iata"]);
			$string = file_get_contents('http://maps.google.com/maps/api/geocode/json?address='.$_POST["name"].'+'.$_POST["iata"].'+airport&sensor=false');
			$result = json_decode($string, true);
			$lat = $result['results'][0]['geometry']['location']['lat'];
			$lng = $result['results'][0]['geometry']['location']['lng'];
			$wpdb->query($wpdb->prepare("INSERT INTO " . $wpdb->prefix . "flightlog_" . $section . " (name, iata, lat, lng) VALUES (%s, %s, %3.7f, %3.7f)", $_POST["name"], $_POST["iata"], $lat, $lng));
		}
		else {
			$wpdb->query($wpdb->prepare("INSERT INTO " . $wpdb->prefix . "flightlog_" . $section . " (name) VALUES (%s)", $_POST["name"]));
		}
	}

	// Update a record
	if(isset($_POST["section"]) && $_POST["section"] == $section && $_POST["Submit"] == "Update") {
                if ($section == "airports") {
			// Get old coordinates
			$results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'flightlog_' . $section . ' WHERE id=' . $_POST["id"]);
			if ($results) {
				foreach ($results as $result) {
					$old_lat = $result->lat;
					$old_lng = $result->lng;
				}
			}

			// Update DB
                        $_POST["iata"] = strtoupper($_POST["iata"]);
                        $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "flightlog_" . $section . " SET name='%s', iata='%s', lat=%3.7f, lng=%3.7f WHERE id=%u", $_POST["name"], $_POST["iata"], $_POST["lat"], $_POST["lng"], $_POST["id"]));

			// Recalc distances if coords were updated with mode than 1 degree
			if ((intval($old_lat) != intval($_POST["lat"])) || (intval($old_lng) != intval($_POST["lng"]))) {
				$results = $wpdb->get_results('SELECT ff.id, fa1.lat AS lat1, fa1.lng AS lng1, fa2.lat AS lat2, fa2.lng AS lng2 FROM ' . $wpdb->prefix . 'flightlog_flights ff JOIN '.$wpdb->prefix.'flightlog_airports fa1 ON fa1.id=ff.airport_from JOIN '.$wpdb->prefix.'flightlog_airports fa2 ON fa2.id=ff.airport_to WHERE fa1.id=' . $_POST["id"] . ' OR fa2.id=' . $_POST["id"]);
				if ($results) {
					foreach ($results as $result) {
						$dist = flightlog_calc_dist($result->lat1, $result->lng1, $result->lat2, $result->lng2);
						$dur = flightlog_calc_dur($dist);

						$wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "flightlog_flights SET distance=%u, duration=%3.1f WHERE id=%u", $dist, $dur, $result->id));
					}
				}
			}
                }
                else {
			$wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "flightlog_" . $section . " SET name='%s' WHERE id=%u", $_POST["name"], $_POST["id"]));
                }
	}

        // Delete a record
        if(isset($_POST["section"]) && $_POST["section"] == $section && $_POST["Submit"] == "Delete") {
		$wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "flightlog_" . $section . " WHERE id=%u", $_POST["id"]));
        }

	echo '<p><b>' . $section . ':</b></p>';

        // Show new record form
        echo '<form name="form_' . $section . '" method="post" action="">';
        echo '<input type=hidden name=section value=' . $section . '>';
        echo '<p><b>Add new:</b><br>Name: ';
        echo '<input type="text" name="name" value="" size="20">';
        if ($section == "airports") {
                echo ' <a href=http://en.wikipedia.org/wiki/IATA_airport_code target=_blank>IATA</a>: <input type="text" name="iata" value="" size="4">';
        }
        echo '<input type="submit" name="Submit" class="button-primary" value="Add">';
        echo '</p>';
        echo '</form>';

	// Display all records
	echo '<p><b>Existing:</b></p>';
	if ($section == "aircrafts") 
		$results = $wpdb->get_results('SELECT ff.id AS ffid, fa.id, fa.name, COUNT(*) AS cnt FROM ' . $wpdb->prefix . 'flightlog_aircrafts fa LEFT JOIN ' . $wpdb->prefix . 'flightlog_flights ff ON ff.aircraft=fa.id GROUP BY fa.id ORDER BY fa.name');
	else if ($section == "airports")
		$results = $wpdb->get_results('SELECT ff.id AS ffid, fa.id, fa.name, COUNT(*) AS cnt, fa.iata, fa.lat, fa.lng FROM ' . $wpdb->prefix . 'flightlog_airports fa LEFT JOIN ' . $wpdb->prefix . 'flightlog_flights ff ON (ff.airport_from=fa.id OR ff.airport_to=fa.id) GROUP BY fa.id ORDER BY fa.name');
	else if ($section == "carriers")
		$results = $wpdb->get_results('SELECT ff.id AS ffid, fc.id, fc.name, COUNT(*) AS cnt FROM ' . $wpdb->prefix . 'flightlog_carriers fc LEFT JOIN ' . $wpdb->prefix . 'flightlog_flights ff ON ff.carrier=fc.id GROUP BY fc.id ORDER BY fc.name');

	if ($results) {
		echo '<table>';
		foreach ($results as $result) {
			if (($result->cnt == 1) && ($refult->ffid == ""))
				$show_del = 1;
			else
				$show_del = 0;

			echo '<tr><td>';
			echo "<a href=# onClick=\"javascript:fl_overlay_add('" . $section  .  "',"  . $result->id . "," . $show_del  .",'" . $result->name . "','" . $result->iata . "','" . $result->lat . "','" . $result->lng . "')\">";
			echo $result->name . '</a></td>';
			if ($section == "airports") {
				if ($result->lat == "") {
					$result->lat = "?";
				}
				else {
					$result->lat = (intval(10*$result->lat))/10;
				}
                                if ($result->lng == "") {
                                        $result->lng = "?";
                                }
                                else {
                                        $result->lng = (intval(10*$result->lng))/10;
                                }
				
				echo '<td>' . $result->iata .'</td>';
				echo '<td>(' . $result->lat . "/" . $result->lng .')</td>';
			}
			echo '</tr>';
		}
		echo '</table>';
	}
}


function flightlog_calc_dist($lat1, $lng1, $lat2, $lng2) {
	global $flightlog_measures;
	if ($flightlog_measures == "Metric")
		$R = 6371;
	else
		$R = 6371/1.609;

	$lat1 = $lat1 * 0.0174;
        $lng1 = $lng1 * 0.0174;
        $lat2 = $lat2 * 0.0174;
        $lng2 = $lng2 * 0.0174;
	
        $dist = acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lng2-$lng1)) * $R;
        $dist = intval($dist);
	$_tmp1 = intval($dist/100);
	$_tmp2 = $dist % 100;
	if ($_tmp2 < 50) 
		$dist2 = $_tmp1 . "50";
	else
		$dist2 = ($_tmp1 + 1) . "00";
	return $dist2;
}


function flightlog_calc_dur($dist) {
        global $flightlog_measures;
	if ($flightlog_measures == "Metric")
		$speed = 650;
	else
		$speed = 650/1.609;
	$_tmp = $dist / $speed;
	return $_tmp;
}

function flightlog_entries() {
	global $current_user;
	global $wpdb;

        if ( !current_user_can( 'publish_pages' ) )  {
                wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
	// Process input, if any
        if(isset($_POST["Submit"]) && $_POST["Submit"] == 'Add' && $_POST['section'] == 'flight') {
		$results1 = $wpdb->get_results('SELECT lat, lng FROM ' . $wpdb->prefix . 'flightlog_airports WHERE id=' . $_POST["from"]);
	        if ($results1) {
                	foreach ($results1 as $result1) {
				$lat1 = $result1->lat;
				$lng1 = $result1->lng;
			}
		}
		$results2 = $wpdb->get_results('SELECT lat, lng FROM ' . $wpdb->prefix . 'flightlog_airports WHERE id=' . $_POST["to"]);
                if ($results2) {
                        foreach ($results2 as $result2) {
                                $lat2 = $result2->lat;
                                $lng2 = $result2->lng;
                        }
                }
		$dist = flightlog_calc_dist($lat1, $lng1, $lat2, $lng2);
		$dur = flightlog_calc_dur($dist);
		$wpdb->query($wpdb->prepare("INSERT INTO " . $wpdb->prefix . "flightlog_flights (user, dt, airport_from, airport_to, carrier, aircraft, distance, duration) VALUES (%u, %s, %u, %u, %u, %u, %u, %3.1f)", $current_user->ID, $_POST["dt"], $_POST["from"], $_POST["to"], $_POST["carrier"], $_POST["aircraft"], $dist, $dur));
	}

        else if (isset($_POST["Submit"]) && $_POST["Submit"] == 'Update') {
		$wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "flightlog_flights SET distance=%u, duration=%3.1f WHERE id=%u", $_POST["distance"], $_POST["duration"], $_POST["id"]));
        }

	else if (isset($_POST["Submit"]) && $_POST["Submit"] == 'Delete') {
		if ($_POST['section'] == 'flight') 
			$wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "flightlog_flights WHERE id=" . $_POST["id"]));
		else if ($_POST['section'] == 'widgetlink') { 
			update_option("flightlog_widgetlink_" . $current_user->user_login, "");
		}
	}
	else if ($_POST['section'] == 'widgetlink') {
		update_option("flightlog_widgetlink_" . $current_user->user_login, $_POST['found_post_id']);
	}

	// Header
	wp_enqueue_style('thickbox'); // needed for find posts div
	wp_enqueue_script('thickbox'); // needed for find posts div
	wp_enqueue_script('media');
	wp_enqueue_script('wp-ajax-response');
	$url = get_option("flightlog_widgetlink_" . $current_user->user_login);
	$widget_link = get_permalink($url);
        echo '<h3>FlightLog Entries</h3>';
	echo '<table><tr><td>Widget link for ' . $current_user->display_name . ': ' . $widget_link . "</td>";
	echo '<td><form name="plugin_form" id="plugin_form" method="post" action="">';
	echo '<input type=hidden name=section value=widgetlink>';
	wp_nonce_field('plugin_nonce');
	find_posts_div();
	if ($url != "") 
		echo " <input type=submit name=Submit value=Delete> ";
	echo "<input type=button value=Find onclick=\"findPosts.open();return false;\"> ";
	echo "</form></td></tr></table>";
        echo '<hr />';

        // Show new record form
        echo '<form name="form_flight" method="post" action="">';
	echo '<input type=hidden name=section value=flight>';
        echo '<p>Date: <input type=text name="dt" id=dt size=10> ';

	echo 'From: <select name="from">';
	$airports = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "flightlog_airports ORDER BY name");
	if ($airports) {
		foreach($airports as $airport) {
			echo '<option value=' . $airport->id . '>' . $airport->name . ' ('. $airport->iata . ')</option>';
		}
	}
	echo "</select>";

        echo ' To: <select name="to">';
        $airports = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "flightlog_airports ORDER BY name");
        if ($airports) {
                foreach($airports as $airport) {
                        echo '<option value=' . $airport->id . '>' . $airport->name . ' ('. $airport->iata . ')</option>';
                }
        }
        echo "</select>";

        echo ' Carrier: <select name="carrier">';
        $carriers = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "flightlog_carriers ORDER BY name");
        if ($carriers) {
                foreach($carriers as $carrier) {
                        echo '<option value=' . $carrier->id . '>' . $carrier->name . '</option>';
                }
        }
        echo "</select>";

        echo ' Aircraft: <select name="aircraft">';
        $aircrafts = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "flightlog_aircrafts ORDER BY name");
        if ($carriers) {
                foreach($aircrafts as $aircraft) {
                        echo '<option value=' . $aircraft->id . '>' . $aircraft->name . '</option>';
                }
        }
        echo "</select>";

        echo ' <input type="submit" name="Submit" class="button-primary" value="Add">';
        echo '</p>';
        echo '</form>';
        echo '<hr />';

	$today = getdate();

        echo '<script language=javascript src=/wp-content/plugins/flightlog/flightlog.js></script>';
        echo '<link type=text/css rel=stylesheet href=/wp-content/plugins/flightlog/flightlog.css>';

	echo '<link rel="stylesheet" type="text/css" media="all" href="/wp-content/plugins/flightlog/jsDatePick_ltr.min.css" />';
	echo '<script type="text/javascript" src="/wp-content/plugins/flightlog/jsDatePick.min.1.3.js"></script>';
	echo '<script type="text/javascript">';
	echo '        window.onload = function(){';
	echo '                new JsDatePick({';
	echo '                        useMode:2,';
	echo '                        target:"dt",';
	echo '                        dateFormat:"%Y-%m-%d",';
	echo '                        selectedDate:{';
	echo '                                day:' . $today["mday"] . ',';        
	echo '                                month:' . $today["mon"] . ',';
	echo '                                year:' . $today["year"] . ',';
	echo '                        },';
	echo '                        limitToToday:true,';
	echo '                        imgPath:"/wp-content/plugins/flightlog/img/",';
	echo '                        weekStartDay:1';
	echo '                });';
	echo '        };';
	echo '</script>';

        // Display entries
        $results = $wpdb->get_results('SELECT ff.id, ff.dt, DATE_FORMAT(ff.dt, "%e.%b.%Y") AS dt2, ff.distance, ff.duration, fa1.name AS from_name, fa1.iata AS from_iata, fa2.name AS to_name, fa2.iata AS to_iata, fc.name AS carrier, fp.name AS aircraft FROM ' . $wpdb->prefix . 'flightlog_flights ff JOIN ' . $wpdb->prefix . 'flightlog_airports fa1 ON fa1.id=ff.airport_from JOIN ' . $wpdb->prefix . 'flightlog_airports fa2 ON fa2.id=ff.airport_to JOIN ' . $wpdb->prefix . 'flightlog_carriers fc ON fc.id=ff.carrier JOIN ' . $wpdb->prefix . 'flightlog_aircrafts fp ON fp.id=ff.aircraft  WHERE user=' . $current_user->ID . ' ORDER BY dt, ff.id');
        if ($results) {
                $counter = 1;
                echo '<p><b>Flights for ' . $current_user->display_name . '</b></p>';
                echo '<table cellpadding=2>';
                echo '<tr><td align=center><i>No</i></td> <td align=center><i>Date</i></td> <td align=center><i>From</i></td> <td align=center><i>To</i></td> <td align=center><i>Carrier</i></td> <td align=center><i>Aircraft</i></td> <td align=center><i>Distance</i></td> <td align=center><i>Duration</i></td></tr>';
                foreach ($results as $result) {
                        echo '<tr>';
                        echo '<td align=right>';
			echo "<a href=# onClick=\"javascript:fl_overlay_add2(" . $result->id . ",'" . $result->dt2 . "','" . $result->from_name . "','" . $result->to_name . "','" .  $result->carrier . "','" . $result->aircraft . "','" . $result->distance . "','" . $result->duration . "')\">";
			echo $counter . '.</td>';
                        echo '<td>' . $result->dt2 . '</td>';
                        echo '<td>' . $result->from_name . ' (' . $result->from_iata . ')</td>';
                        echo '<td>' . $result->to_name . ' (' . $result->to_iata . ')</td>';
                        echo '<td>' . $result->carrier . '</td>';
                        echo '<td>' . $result->aircraft . '</td>';
                        echo '<td align=right>' . $result->distance . '</td>';
                        echo '<td>' . $result->duration . '</td>';
                        echo '</tr>';
                        $counter++;
                }
                echo '</table>';
        }
}


// Widget

class FlightLog_Widget extends WP_Widget {
	public function __construct() {
		// widget actual processes
		parent::__construct(
	 		'flightlog_widget', // Base ID
			'FlightLog_Widget', // Name
			array( 'description' => __( 'FlightLog Widget', 'text_domain' ), ) // Args
		);
	}

 	public function form( $instance ) {
		// outputs the options form on admin
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
	        $instance = $old_instance;
        	$instance['title'] = strip_tags($new_instance['title']);
	        return $instance;
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget
	        global $current_user;
	        global $wpdb;
		global $flightlog_measures;

		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

		$summaries = $wpdb->get_results("SELECT COUNT(*) AS cnt, SUM(distance) AS distance, ceil(SUM(duration)) AS duration, u.user_login, u.display_name FROM " . $wpdb->prefix . "flightlog_flights ff JOIN " . $wpdb->prefix . "users u ON ff.user=u.id GROUP BY user");
		if ($summaries) {
			echo '<ul>';
			foreach ($summaries as $summary) {
			        $url = get_option("flightlog_widgetlink_" . $summary->user_login);
			        $widget_link = get_permalink($url);
				echo '<li>';
				if ($url != "") 
					echo '<a href="'.$widget_link.'">';
				echo $summary->display_name . '</a>: ' . $summary->cnt . ', ' . $summary->distance;
				if ($flightlog_measures == 'Metric') 
					echo ' km, '; 
				else
					echo ' mi, ';
				echo $summary->duration . ' h.</li>';
			}
			echo "</ul>";
		}

		echo $after_widget;		
	}

}


function flightlog_ui( $atts ) {
	global $wpdb;
	global $flightlog_measures;
	$input = shortcode_atts( array(
		'username' => '',
		'year_from' => '',
		'year_to' => '',
	), $atts);

	$where = "";
	$web = "";
	$year_from = 0;
	$year_to = 0;
	if (($input['year_from'] != '') && ($input['year_to'] != '')) {
		$web .= "from " . $input['year_from'] . " to " . $input['year_to'];
		$where = " AND YEAR(dt)>=" . $input['year_from'] . " AND YEAR(dt)=<" . $input['year_to'];
		$year_from = $input['year_from'];
		$year_to = $input['year_to'];
	}
	else if ($input['year_from'] != '') {
                $web .= "from " . $input['year_from'] . " till now ";
		$where = " AND YEAR(dt)>=" . $input['year_from'];
		$year_from = $input['year_from'];
	}
        else if ($input['year_to'] != '') {
                $web .= " till " . $input['year_to'];
		$where = " AND YEAR(dt)=<" . $input['year_to'];
		$year_to = $input['year_to'];
	}

	$user = get_user_by('login', $input['username']);
	if ($user->ID == 0)
		return;

	echo 'Map for user ' . $user->first_name;
	if ($web != "") 
		echo '(' . $web . ')';
	echo ':';

        $summaries = $wpdb->get_results("SELECT COUNT(*) AS cnt, SUM(distance) AS distance, ceil(SUM(duration)) AS duration, u.display_name FROM " . $wpdb->prefix . "flightlog_flights ff JOIN " . $wpdb->prefix . "users u ON ff.user=u.id WHERE user=" . $user->ID . $where);
        if ($summaries) {
	        echo '<ul>';
        	foreach ($summaries as $summary) {
                	echo '<li>Total flights: ' . $summary->cnt . '</li>';
			echo '<li>Total distance: ' . $summary->distance;
                        if ($flightlog_measures == 'Metric') 
                                echo ' km'; 
                        else
                                echo ' mi';
			echo '</li><li>Total time: ' . $summary->duration . ' h.</li>';
                }
                echo "</ul>";
        }

	echo '<div id="flightlog_gmaps" style="width:640px; height:480px"></div>';
	echo '<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=&sensor=false"></script>';
	echo '<script language=javascript src=/wp-content/plugins/flightlog/flightlog.js></script>';
	echo '<script type="text/javascript">window.onload=fl_gsux('.$user->ID.','.$year_from.','.$year_to.'); window.onload=fl_gmaps;</script>';
}

// Hooks
register_activation_hook(__FILE__,'flightlog_db_create');
add_action('admin_menu', 'flightlog_admin_menu');
add_action( 'widgets_init', create_function( '', 'register_widget( "FlightLog_Widget" );' ) );
add_shortcode( 'flightlog', 'flightlog_ui' );

?>
