<?php

require('../../../wp-config.php');

//$server = "localhost";
//$username = "root";
//$password = "";
//$database = "wordpress";
//$table_prefix = "wp_";

// Opens a connection to a MySQL server.
$connection = mysql_connect (DB_HOST, DB_USER, DB_PASSWORD);
if (!$connection) {
	die('Not connected : ' . mysql_error());
}

// Sets the active MySQL database.
$db_selected = mysql_select_db(DB_NAME, $connection);
if (!$db_selected) {
	die('Can\'t use db : ' . mysql_error());
}

$where = "";
if (isset($_GET['year_from']) && isset($_GET['year_to']))
	$where .= " AND YEAR(dt)>= " . $_GET['year_from'] . " AND YEAR(dt)=< " . $_GET['year_to'];
else if (isset($_GET['year_from']))
	$where .= " AND YEAR(dt)>= " . $_GET['year_from'];
else if (isset($_GET['year_to']))
	$where .= " AND YEAR(dt)=< " . $_GET['year_to'];

// Selects all the rows in the markers table.
mysql_query('SET names ' . DB_CHARSET);
$result = mysql_query('SELECT ff.id, ff.dt, DATE_FORMAT(ff.dt, "%e.%b.%Y") AS dt2, ff.distance, ff.duration, fa1.name AS from_name, fa1.iata AS from_iata, fa1.lat AS lat1, fa1.lng AS lng1, fa2.lat AS lat2, fa2.lng AS lng2, fa2.name AS to_name, fa2.iata AS to_iata, fc.name AS carrier, fp.name AS aircraft FROM ' . $table_prefix . 'flightlog_flights ff JOIN ' . $table_prefix . 'flightlog_airports fa1 ON fa1.id=ff.airport_from JOIN ' . $table_prefix . 'flightlog_airports fa2 ON fa2.id=ff.airport_to JOIN ' . $table_prefix . 'flightlog_carriers fc ON fc.id=ff.carrier JOIN ' . $table_prefix . 'flightlog_aircrafts fp ON fp.id=ff.aircraft  WHERE user=' . $_GET['userID'] . $where);

if (!$result) {
	die('Invalid query: ' . mysql_error());
}

// Creates the Document.
$dom = new DOMDocument('1.0', 'UTF-8');

// Creates the root KML element and appends it to the root document.
$node = $dom->createElementNS('http://earth.google.com/kml/2.1', 'kml');
$parNode = $dom->appendChild($node);

// Creates a KML Document element and append it to the KML element.
$dnode = $dom->createElement('Document');
$docNode = $parNode->appendChild($dnode);

// Creates the two Style elements, one for flightlog and one for bar, and append the elements to the Document element.
$restStyleNode = $dom->createElement('Style');
$restStyleNode->setAttribute('id', 'flightlogStyle');
$restIconstyleNode = $dom->createElement('IconStyle');
$restIconstyleNode->setAttribute('id', 'flightlogIcon');
$restIconNode = $dom->createElement('Icon');
$restHref = $dom->createElement('href', 'http://maps.google.com/mapfiles/kml/pal2/icon63.png');
$restIconNode->appendChild($restHref);
$restIconstyleNode->appendChild($restIconNode);
$restStyleNode->appendChild($restIconstyleNode);
$docNode->appendChild($restStyleNode);

// Iterates through the MySQL results, creating one Placemark for each row.
while ($row = @mysql_fetch_assoc($result)) {
	//$result->dt2 . $result->from_name . $result->to_name .  $result->carrier . $result->aircraft . $result->distance . $result->duration
    //$result->lat1 $result->lng1 $result->lat2 $result->lng2
/*
        // Check airport FROM
        if (!isset($check_airport[$row['from_iata']])) {
		// Creates a Placemark and append it to the Document.
		$node = $dom->createElement('Placemark');
		$placeNode = $docNode->appendChild($node);

		// Creates an id attribute and assign it the value of id column.
		$placeNode->setAttribute('id', 'placemark' . $row['id']);

		// Create name, and description elements and assigns them the values of the name and address columns from the results.
		$nameNode = $dom->createElement('name',htmlentities($row['from_name']));
		$placeNode->appendChild($nameNode);
		$descNode = $dom->createElement('description', $row['from_iata']);
		$placeNode->appendChild($descNode);
		//$styleUrl = $dom->createElement('styleUrl', '#' . $row['type'] . 'Style');
		//$placeNode->appendChild($styleUrl);

		// Creates a Point element.
		$pointNode = $dom->createElement('Point');
		$placeNode->appendChild($pointNode);

	        // Creates a coordinates element and gives it the value of the lng and lat columns from the results.
        	$coorStr = $row['lng1'] . ','  . $row['lat1'];
	        $coorNode = $dom->createElement('coordinates', $coorStr);
        	$pointNode->appendChild($coorNode);

		// Remember this point
		$check_airport[$row['from_iata']] = 1;
	}

        // Check airport TO
        if (!isset($check_airport[$row['from_iata']])) {
                // Creates a Placemark and append it to the Document.
                $node = $dom->createElement('Placemark');
                $placeNode = $docNode->appendChild($node);

                // Creates an id attribute and assign it the value of id column.
                $placeNode->setAttribute('id', 'placemark' . $row['id']);

                // Create name, and description elements and assigns them the values of the name and address columns from the results.
                $nameNode = $dom->createElement('name',htmlentities($row['to_name']));
                $placeNode->appendChild($nameNode);
                $descNode = $dom->createElement('description', $row['to_iata']);
                $placeNode->appendChild($descNode);
                //$styleUrl = $dom->createElement('styleUrl', '#' . $row['type'] . 'Style');
                //$placeNode->appendChild($styleUrl);

                // Creates a Point element.
                $pointNode = $dom->createElement('Point');
                $placeNode->appendChild($pointNode);

                // Creates a coordinates element and gives it the value of the lng and lat columns from the results.
                $coorStr = $row['lng2'] . ','  . $row['lat2'];
                $coorNode = $dom->createElement('coordinates', $coorStr);
                $pointNode->appendChild($coorNode);

                // Remember this point
                $check_airport[$row['to_iata']] = 1;
        }
*/
	// Check FROM-TO and TO-FROM
	if (isset($check_trip[$row['from_iata'].$row['to_iata']])) {
		$check_trip[$row['from_iata'].$row['to_iata']] ++;
		$newNode = $dom->createElement('description', $check_trip[$row['from_iata'].$row['to_iata']] . ' flights, last '.$row['dt2'] . ', ' . $row['carrier'] . ', ' . $row['aircraft']);
		$placeNode[$row['from_iata'].$row['to_iata']]->replaceChild($newNode, $descNode[$row['from_iata'].$row['to_iata']]);
		$descNode[$row['from_iata'].$row['to_iata']] = $newNode;
	}
	else if (isset($check_trip[$row['to_iata'].$row['from_iata']])) {
		$check_trip[$row['to_iata'].$row['from_iata']] ++ ;
		$newNode = $dom->createElement('description', $check_trip[$row['to_iata'].$row['from_iata']] . ' flights, last '.$row['dt2'] . ', ' . $row['carrier'] . ', ' . $row['aircraft']);
		$placeNode[$row['to_iata'].$row['from_iata']]->replaceChild($newNode, $descNode[$row['to_iata'].$row['from_iata']]);
		$descNode[$row['to_iata'].$row['from_iata']] = $newNode;
	}
	else {
                $node = $dom->createElement('Placemark');
                $placeNode[$row['from_iata'].$row['to_iata']] = $docNode->appendChild($node);

		//Create an id attribute and assign it the value of id column
		$placeNode[$row['from_iata'].$row['to_iata']]->setAttribute('id', $row['from_iata'].$row['to_iata']);

		//Create name, description, and address elements and assign them the values of the name, type, and address columns from the results
		$nameNode = $dom->createElement('name', $row['from_name'].' - '.$row['to_name'].' ('.$row['from_iata'].'-'.$row['to_iata'].')');
		$placeNode[$row['from_iata'].$row['to_iata']]->appendChild($nameNode);
		$descNode[$row['from_iata'].$row['to_iata']] = $dom->createElement('description', $row['dt2'] . ', ' . $row['carrier'] . ', ' . $row['aircraft']);
		$placeNode[$row['from_iata'].$row['to_iata']]->appendChild($descNode[$row['from_iata'].$row['to_iata']]);

		//Create a LineString element
		$lineNode = $dom->createElement('LineString');
		$placeNode[$row['from_iata'].$row['to_iata']]->appendChild($lineNode);
		$exnode = $dom->createElement('extrude', '1');
		$lineNode->appendChild($exnode);
		$almodenode =$dom->createElement('altitudeMode','relativeToGround');
		$lineNode->appendChild($almodenode);

		$coorNode = $dom->createElement('coordinates',$row['lng1'].','.$row['lat1'].',100 '.$row['lng2'].','.$row['lat2'].',100 ');
		$lineNode->appendChild($coorNode);

		$check_trip[$row['from_iata'].$row['to_iata']] = 1;
	}
}

$kmlOutput = $dom->saveXML();
header('Content-type: application/vnd.google-earth.kml+xml');
echo $kmlOutput;
?>
