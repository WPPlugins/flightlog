=== Plugin Name ===
Contributors: assen.totin
Donate link: http://zavedil.com/
Tags: flight,log,map,airport,aircraft,carrier
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

FlightLog is a Wordpress plugin which helps you track your flights, gives a nice summary of them in your sidebar and even plots them on a map.

== Description ==

= Configuration =

Log on with Admin privileges and go to Plugins->FlightLog to configure the plugin:

* Airlines: enter a name for each airline you used
* Aircrafts: enter a name for each type of aircraft you used (hint: it is better to group them by families, i.e. use 'Boeing 737' for the whole family of B737-100 to B737-NG, or 'Airbus A320' for all variants of A318, A319, A320, A321).
* Airports: for each airport you used, enter a city and IATA code (a link to Wikipedia's list of IATA codes is provided). FlightLog will automatically figure out the latitude and longitude of the airport. If they are not fetched (seeing question marks instead of numbers), or if they are incorrect, click the name of the entry to edit them manually. Note: if you change the coordinates, FlighLog will automatically recalculate all flight distances and duration which use this airport.

To edit an entry, click its name (hit the "Update" button when ready). Note that this will update all existing records that refer to this entry. If an entry (airport, airline, aircraft) is not used in any flight, there will also be a "Delete" button which you can use to delete the entry (if you need to delete an entry, but it is used, first delete all flights that us it).

From the same page you can manage the measurement units: metric (distances calculated in kilometers) or US (in miles). If you change the measurement units, FlightLog will automatically re-calculate all flights in the new ones.

= Entering a Flight =

Log on as Editor and go to Tools->FlightLog. Chose date, airports from and to, airline and aircraft. FlightLog will automatically calculate the distance (based on cached airport coordinates) and flight duration. If you need to correct them, click the number of the flight. From the same screen you can also delete a flight.

FlightLog will auto detect the user that is currently logger in, so the new flight will be recorded on his account (meaning that you have separate log for separate users on your system).

= Displaying Flight Summary =

To display the flight summary, add the FlightLog widget to your desired sidebar. It will display total number of flights, distance and time per each user that has flights.

= Displaying Flight Map =

To display a map with the flights, create a new post or page where you want the map displayed and enter tag like this:

    [flightlog username=XXX]

where XXX is the username for which you want the map displayed. You can limit the time interval to certain years by also adding  `year_from=YYYY` and/or `year_to=YYYY` in the square brackets after the username. Each route will be shown on the map; click the route to get additional info (departure and destination airports, number of flights on it and details on the last: date, carrier and aircraft).

To link a post or page with a map to the widget (so that the name in the widget becomes a link to the post or page), go to same page where you manage your flights find the "Widget link" section on top; then click "Find" and select the proper post or page. To change the link, use the same procedure. To remove the link, use the "Delete" button next to it.

== Installation ==

*Automatic installation*

1. Select the FlightLog plugin from the plugin installation page in Wordpress.
2. Activate the plugin through the 'Plugins' menu in Wordpress.

*Manual installation*

1. Get the ZIP archive from here: [http://www.zavedil.com/wp-content/uploads/2012/12/flightlog-1.0.0.zip](http://www.zavedil.com/wp-content/uploads/2012/12/flightlog-1.0.0.zip).
2. Unzip and put the `flightlog` directory inside your `plugins` directory (which itself is usually in `<DocumentRoot>/wp-content`).
3. Activate the plugin through the 'Plugins' menu in Wordpress.

== Frequently Asked Questions ==

= What is an IATA code? Where do I get it? =

The IATA code is a three-letter code which is (almost) unique to an airport. FlightLog needs the IATA code of the airport to properly fetch its coordinates. Usually the IATA code of the airport is on your ticket; Wikipedia has a list of all IATA codes - just click the link "IATA" when entering a new flight.

= FlightLog does not get coordinates for a given airport or gets them wrong =

FlightLog uses Google geocode API to detect airports' coordinates. If it fails to get proper coordinates (incorrect values are displayed, or "?"), click the name of the airport and enter them manually. For all flights that feature this airport, FlightLog will then automatically recalculate the distance and flight time.

= FlightLog wrongly calculates flight distance =

FlightLog calculates flight distance based on airports' coordinates. Coordinates are fetched only once, when an airport is entered. If distance of a flight seems incorrect, make sure the coordinates of both departure and arrival airports are correct (see previous question for details). Do note that flighs rarely happen on a straight line; to accommodate for this, FlightLog rounds up distances to the closest 50 km or miles. If you want, you can still manually adjust flight distance: click the flight number to edit it.

= FlightLog wrongly calculates flight time =

FlightLog calculates flight's time based on the flight's distance. Jet crafts usually fly at around 800-850 km/h (500-530 mph) at cruising altitude, but significantly slower at take-off and landing - as low as 200-250 km/h (130-160 mph). To accommocate for this, FlightLog uses an average speed ot 650 km/h (400 mph). If you want, you can still manually adjust flight time the same way you can adjust flight distance. If you flew not a jet, but a propeller aircraft, you'd likely want to adjust the time.

= I prefer to have the distances in miles, not in kilometers =

Log with Admin privileges and go to the Plugins->FlightLog page. On top, fidn the `Measurement System` reading and click `Metric`. Change it to `US`. FlightLog will automatically recalculate distances of all flights in miles.

= The "Delete" button is missing for some entries =

If an airport, aircraft or airline is used in a flight, you won't be able to delete it. First delete all flights it appears in, then the "Delete" button will show up.

== Screenshots ==

1. Screenshot from the plugin showing the flight summary for all users (right hand side, middle), flight details for one of them (top left) and (part of) the map of flights. 

== Changelog ==

= 1.0.1 =
* Fixed an issue with deleting flights.

= 1.0.0 =
* Initial release.

== Contacts ==

Home Page: [http://www.zavedil.com/wordpress-plugin-flightlog](http://www.zavedil.com/wordpress-plugin-flightlog)

Email: [assen.totin@gmail.com](mailto:assen.totin@gmail.com)

