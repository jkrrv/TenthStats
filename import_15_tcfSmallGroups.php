<?php

require_once '_db.php';

$src = "Manual";

if (($handle = fopen("K:/SkyDrive/Tenth/Data/TCF/smallGroupsFall2015.csv", "r")) !== FALSE) {
	$data = fgetcsv($handle, 1000, ",");
	$weeks = [];

	foreach ($data as $col => $item) {
		if ($col < 1) {
			continue;
		}

		$weeks[$col] = new DateTime($item . " 2015");
	}

	$g = null;
	$events = [];

	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		set_time_limit(3);
		if ($data[0] == "") {
			$g = null;
			$events = [];
			continue;
		}

		if ($g == null) {
			$g = new Grp($data[0]);

			if ($g->id == null) {
				$g->name = $data[0];
				$g->type = "Small";

				if (stripos($data[0], "Women") !== false) {
					$g->gender = 2;
				} else if (stripos($data[0], "Men") !== false) {
					$g->gender = 1;
				} else if (stripos($data[0], "Guys") !== false) {
					$g->gender = 1;
				}

				$g->commit();
			}

			var_dump($g);

			foreach ($data as $col => $val) {
				if ($col < 1)
					continue;

				if ($val == 0 || $val == '') {
					continue;
				}

				//TODO get actual day of week.
				//TODO get event locations.
				$eventName = "Small Group " . $g->id . " on " . $weeks[$col]->format('j M Y');
				$e = new Event($eventName);
				if ($e->id == null) {
					$e->name = $eventName;
					// location?
					$e->grp = $g->id;
					$e->dt = $weeks[$col]->getTimestamp();

					$e->commit();
				}
				$events[$col] = $e;
			}
		} else {
			try {
				$p = new Person($data[0]);
			} catch (Exception $ex) {
				echo "! Skipping " . $data[0] . " due to " . $ex->getMessage() . "<br />";
				continue;
			}
			if ($p->id == null) {
				echo "! Skipping " . $data[0] . "<br />";
				continue;
			}
			echo $p;

			$gm = new GrpMem("" . $p->id . "." . $g->id);

			for ($col = count($data); --$col > 1;) {
				if (!isset($events[$col])) {
					continue;
				}

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

			echo "<br />";


		}


	}
}






// Spring
if (($handle = fopen("K:/SkyDrive/Tenth/Data/TCF/smallGroupsSpring2016.csv", "r")) !== FALSE) {
	$data = fgetcsv($handle, 1000, ",");
	$weeks = [];

	foreach ($data as $col => $item) {
		if ($col < 1) {
			continue;
		}

		$weeks[$col] = new DateTime($item . " 2016");
	}

	$g = null;
	$events = [];

	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		set_time_limit(3);
		if ($data[0] == "") {
			$g = null;
			$events = [];
			continue;
		}

		if ($g == null) {
			$g = new Grp($data[0]);

			if ($g->id == null) {
				$g->name = $data[0];
				$g->type = "Small";

				if (stripos($data[0], "Women") !== false) {
					$g->gender = 2;
				} else if (stripos($data[0], "Men") !== false) {
					$g->gender = 1;
				} else if (stripos($data[0], "Guys") !== false) {
					$g->gender = 1;
				}

				$g->commit();
			}

			var_dump($g);

			foreach ($data as $col => $val) {
				if ($col < 1)
					continue;

				if ($val == 0 || $val == '') {
					continue;
				}

				//TODO get actual day of week.
				//TODO get event locations.
				$eventName = "Small Group " . $g->id . " on " . $weeks[$col]->format('j M Y');
				$e = new Event($eventName);
				if ($e->id == null) {
					$e->name = $eventName;
					// location?
					$e->grp = $g->id;
					$e->dt = $weeks[$col]->getTimestamp();

					$e->commit();
				}
				$events[$col] = $e;
			}
		} else {
			try {
				$p = new Person($data[0]);
			} catch (Exception $ex) {
				echo "! Skipping " . $data[0] . " due to " . $ex->getMessage() . "<br />";
				continue;
			}
			if ($p->id == null) {
				echo "! Skipping " . $data[0] . "<br />";
				continue;
			}
			echo $p;

			$gm = new GrpMem("" . $p->id . "." . $g->id);

			for ($col = count($data); --$col > 1;) {
				if (!isset($events[$col])) {
					continue;
				}

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

			echo "<br />";



		}




	}



}