<?php

require_once '_db.php';

$src = "Manual";

if (($handle = fopen("K:/SkyDrive/Tenth/Data/TCF/SpecialEvents.csv", "r")) !== FALSE) {
	$data = fgetcsv($handle, 1000, ","); // skip header row.
	$data = fgetcsv($handle, 1000, ",");
	$events = [];
	foreach ($data as $col => $item) {
		if ($col < 2) {
			continue;
		}

		$events[$col] = new Event($item);
	}

	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

		$person = new Person($data[0] . "," . $data[1]);

		if ($person->id == 0) {
			echo "! Skipping " . $data[0] . " " . $data[1] . "<br />";
			continue;
		}


		foreach ($data as $col => $item) {
			if ($col < 2)
				continue;

			$opp = new Opp($events[$col]->id, $person->id, $src);
			$opp->source = $src;
			$opp->event = $events[$col]->id;
			$opp->person = $person->id;
			$opp->confidence = 100;
			$opp->status = ($item == '' ? -1 : 1);
			$opp->commit();
		}

		echo "added " . $person . "<br />";
	}
}