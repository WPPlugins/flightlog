function fl_overlay_add_sys(measures) {
        var overlay = document.createElement("div");
        overlay.setAttribute("id","overlay");
        overlay.setAttribute("class", "overlay");
        document.body.appendChild(overlay);
        var el = document.getElementById("overlay");
        var html = "<table width=100% height=100%><tr><td align=center valign=center><form method=post action=\"\">";
        html += "<table bgcolor=#333 cellpadding=4 cellspacing=4>";
        html += "<tr><td><font color=#FFF><b>Measures:</b></td><td><select name=measures>";
	var selected;
	measures=="Metric" ? selected=" selected " : selected="";
	html += "<option value=Metric" + selected + ">Metric</option>";
	measures=="US" ? selected=" selected " : selected="";
	html += "<option value=US" + selected + ">US</option>";
	html += "</select></td></tr>";
        html += "<tr><td></td><td><input type=submit name=Submit value=Update> ";
        html += "<input type=button name=Cancel value=Cancel onClick=javascript:fl_overlay_del()> ";
        html += "</td></tr></table>";
        html += "</form></td></tr></table>";
        el.innerHTML = html;
}


function fl_overlay_add(section, id, show_del, name, iata, lat, lng) {
	var overlay = document.createElement("div");
	overlay.setAttribute("id","overlay");
	overlay.setAttribute("class", "overlay");
	document.body.appendChild(overlay);
	var el = document.getElementById("overlay");

	var html = "<table width=100% height=100%><tr><td align=center valign=center><form method=post action=\"\">";
	html += "<input type=hidden name=section value=" + section + ">";
	html += "<input type=hidden name=id value=" + id + ">";
	html += "<table bgcolor=#333 cellpadding=4 cellspacing=4>";
	html += "<tr><td><font color=#FFF><b>Name:</b></td><td><input type=text name=name size=20 value='" + name + "'></td></tr>";
	if (section == "airports") {
		html += "<tr><td><font color=#FFF><b>IATA:</b></td><td><input type=text size=4 name=iata value=\"" + iata + "\"></td></tr>";
                html += "<tr><td><font color=#FFF><b>Latitude:</b></td><td><input type=text size=12 name=lat value=\"" + lat + "\"></td></tr>";
                html += "<tr><td><font color=#FFF><b>Longtitude:</b></td><td><input type=text size=12 name=lng value=\"" + lng + "\"></td></tr>";
        }
	html += "<tr><td></td><td><input type=submit name=Submit value=Update> ";

	if (show_del == 1) {
		html += "<input type=submit name=Submit value=Delete> ";
	}

	html += "<input type=button name=Cancel value=Cancel onClick=javascript:fl_overlay_del()> ";
	html += "</td></tr></table>";
	html += "</form></td></tr></table>";

   	el.innerHTML = html;
}


function fl_overlay_add2(id, date, from, to, carrier, aircraft, distance, duration) {
        var overlay = document.createElement("div");
        overlay.setAttribute("id","overlay");
        overlay.setAttribute("class", "overlay");
        document.body.appendChild(overlay);
        var el = document.getElementById("overlay");

        var html = "<table width=100% height=100%><tr><td align=center valign=center><form method=post action=\"\">";
        html += "<input type=hidden name=id value=" + id + ">";
	html += "<input type=hidden name=section value=flight>";
        html += "<table bgcolor=#333 cellpadding=4 cellspacing=4>";
        html += "<tr><td><font color=#FFF><b>Date:</b></td><td><font color=#FFF>" + date  + "</td></tr>";
        html += "<tr><td><font color=#FFF><b>From:</b></td><td><font color=#FFF>" + from  + "</td></tr>";
        html += "<tr><td><font color=#FFF><b>To:</b></td><td><font color=#FFF>" + to  + "</td></tr>";
	html += "<tr><td><font color=#FFF><b>Carrier:</b></td><td><font color=#FFF>" + carrier  + "</td></tr>";
	html += "<tr><td><font color=#FFF><b>Aircraft:</b></td><td><font color=#FFF>" + aircraft  + "</td></tr>";
        html += "<tr><td><font color=#FFF><b>Distance:</b></td><td><input type=text size=4 name=distance value=\"" + distance + "\"></td></tr>";
        html += "<tr><td><font color=#FFF><b>Duration:</b></td><td><input type=text size=12 name=duration value=\"" + duration + "\"></td></tr>";
        html += "<tr><td></td><td><input type=submit name=Submit value=Update> ";
        html += "<input type=submit name=Submit value=Delete> ";
        html += "<input type=button name=Cancel value=Cancel onClick=javascript:fl_overlay_del()> ";
        html += "</td></tr></table>";
        html += "</form></td></tr></table>";

        el.innerHTML = html;
}


function fl_overlay_del() {
   document.body.removeChild(document.getElementById("overlay"));
}


// Cover up for Google Maps bug when calling the init function with args
var fl_gmaps_path;
function fl_gsux(userID, year_from, year_to) {
        var ts = new Date().getTime();
        fl_gmaps_path = location.protocol + '//' + location.hostname + (location.port ? ':'+location.port: '') + '/wp-content/plugins/flightlog/kml.php?userID=' + userID + '&ts=' + ts;
        if (year_from > 0)
                fl_gmaps_path += "&year_from=" . year_from;
        if (year_to > 0)
                fl_gmaps_path += "&year_to=" . year_to;
}

function fl_gmaps() {
	var mapOptions = {
		center: new google.maps.LatLng(0, 0),
		zoom: 2,
		mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var map = new google.maps.Map(document.getElementById("flightlog_gmaps"), mapOptions);
	var kmzLayer = new google.maps.KmlLayer(fl_gmaps_path);
	kmzLayer.setMap(map);
}

