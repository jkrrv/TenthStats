<?php

require_once '_db.php';

$src = "Manual";

$g = new Grp("TCF Sunday");

if (($handle = fopen("K:/SkyDrive/Tenth/Data/TCF/sundayFall2015.csv", "r")) !== FALSE) {
	$data = fgetcsv($handle, 1000, ",");
	$events = [];


	for ($col = 2; count($data) > $col; $col++) {
		if ($data[$col] == '')
			continue;
		$date = new DateTime($data[$col] . " 2015");

		$eventName = "TCF Sunday on " . $date->format('j M Y');
		$e = new Event($eventName);
		if ($e->id == null) {
			$e->name = $eventName;
			// location?
			$e->grp = $g->id;
			$e->dt = $date->getTimestamp();

			$e->commit();
		}
		$events[$col] = $e;
	}


	while(!!($data = fgetcsv($handle, 1000, ","))) {
		if ($data[0] == '' || $data[1] == '') {
			echo "Skipping " . $data[0] . " " . $data[1] . " Due to insufficient number of names. <br />";
			continue;
		}
		$p = new Person($data[0] . "," . $data[1]);
		if ($p->id == 0) {
			echo "Skipping " . $data[0] . " " . $data[1] . " due to not being recognized. <br />";
			continue;
		}

		$gm = new GrpMem("" . $p->id . "." . $g->id);

		foreach($events as $col => $event) {
			$opp = new Opp($events[$col]->id, $p->id, $src);
			$opp->person = $p->id;
			$opp->event = $events[$col]->id;
			$opp->confidence = 100;
			$opp->source = $src;

			if ($data[$col] != '') { // present
				if ($gm->dJoin == null) {
					$gm->dJoin = $events[$col]->dt;
				} else {
					$gm->dJoin = min($gm->dJoin, $events[$col]->dt);
				}
				$gm->commit();

				$opp->status = 1;
				$opp->commit();


			} else { // absent

				$opp->status = -1;

				if ($gm->dJoin == null)
					continue;

				if ($gm->dJoin < $events[$col]->dt && ($gm->dLeft == null || $gm->dLeft > $events[$col]->dt)) {
					// person is part of the group and thus expected.
					$opp->commit();
				}

			}
		}
		echo "Imported $p <br />";

	}

}

set_time_limit(30);


if (($handle = fopen("K:/SkyDrive/Tenth/Data/TCF/sundaySpring2016.csv", "r")) !== FALSE) {
	$data = fgetcsv($handle, 1000, ",");
	$events = [];


	for ($col = 2; count($data) > $col; $col++) {
		if ($data[$col] == '')
			continue;
		$date = new DateTime($data[$col] . " 2016");

		$eventName = "TCF Sunday on " . $date->format('j M Y');
		$e = new Event($eventName);
		if ($e->id == null) {
			$e->name = $eventName;
			// location?
			$e->grp = $g->id;
			$e->dt = $date->getTimestamp();

			$e->commit();
		}
		$events[$col] = $e;
	}


	while(!!($data = fgetcsv($handle, 1000, ","))) {
		if ($data[0] == '' || $data[1] == '') {
			echo "Skipping " . $data[0] . " " . $data[1] . " Due to insufficient number of names. <br />";
			continue;
		}
		$p = new Person($data[0] . "," . $data[1]);
		if ($p->id == 0) {
			echo "Skipping " . $data[0] . " " . $data[1] . " due to not being recognized. <br />";
			continue;
		}

		$gm = new GrpMem("" . $p->id . "." . $g->id);

		foreach($events as $col => $event) {
			$opp = new Opp($events[$col]->id, $p->id, $src);
			$opp->person = $p->id;
			$opp->event = $events[$col]->id;
			$opp->confidence = 100;
			$opp->source = $src;

			if ($data[$col] != '') { // present
				if ($gm->dJoin == null) {
					$gm->dJoin = $events[$col]->dt;
				} else {
					$gm->dJoin = min($gm->dJoin, $events[$col]->dt);
				}
				$gm->commit();

				$opp->status = 1;
				$opp->commit();


			} else { // absent

				$opp->status = -1;

				if ($gm->dJoin == null)
					continue;

				if ($gm->dJoin < $events[$col]->dt && ($gm->dLeft == null || $gm->dLeft > $events[$col]->dt)) {
					// person is part of the group and thus expected.
					$opp->commit();
				}

			}
		}
		echo "Imported $p <br />";

	}

}