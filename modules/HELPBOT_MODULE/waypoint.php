<?php

if (preg_match("/^waypoint ([0-9\\.]+) ([0-9\\.]+) ([0-9]+)$/i", $message, $arr)) {
	$x_coords = $arr[1];
	$y_coords = $arr[2];
	$playfield_id = $arr[3];
	
	$playfield = Towers::get_playfield_by_id($playfield_id);
	if ($playfield === null) {
		$playfield_name = $playfield_id;
	} else {
		$playfield_name = $playfield->short_name;
	}
} else if (preg_match("/^waypoint ([0-9\\.]+) ([0-9\\.]+) (.+)$/i", $message, $arr)) {
	$x_coords = $arr[1];
	$y_coords = $arr[2];
	$playfield_name = $arr[3];
	
	$playfield = Towers::get_playfield_by_name($playfield_name);
	if ($playfield === null) {
		$this->send("Could not find playfield '$playfield_name'", $sendto);
		return;
	} else {
		$playfield_id = $playfield->id;
		$playfield_name = $playfield->short_name;
	}
} else if (preg_match("/^waypoint \\(?([0-9\\.]+) ([0-9\\.]+) y ([0-9\\.]+) ([0-9]+)\\)?$/i", $message, $arr)) {
	$x_coords = $arr[1];
	$y_coords = $arr[2];
	$playfield_id = $arr[4];

	$playfield = Towers::get_playfield_by_id($playfield_id);
	if ($playfield === null) {
		$playfield_name = $playfield_id;
	} else {
		$playfield_name = $playfield->short_name;
	}
} else {
	$syntax_error = true;
	return;
}

$link = $this->makeLink("waypoint: {$x_coords}x{$y_coords} {$playfield_name}", "/waypoint {$x_coords} {$y_coords} {$playfield_id}", 'chatcmd');	
$blob = "<header>:::::: Waypoint ::::::<end>\n\nClick here to use waypoint: $link";
$msg = $this->makeLink("waypoint: {$x_coords}x{$y_coords} {$playfield_name}", $blob, 'blob');
$this->send($msg, $sendto);

?>