<?php

require_once '_db.php';

global $pdo;


$q = $pdo->query("SELECT FamilyNumber, IndividualNumber, LastAttended FROM ATClass WHERE (
					ATClass.LastAttended <> '' AND 
					ATClass.Class = 'Communion Attendance Record' AND
					ATClass.DateRemoved = ''
					)");



while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
	$p = new Person($r['FamilyNumber'] . "." . $r['IndividualNumber']);

	if ($p->id == 0)
		continue;

	$dt = new DateTime($r['LastAttended']);

	$am = true;

	if ($dt->format('N') != 7)
		continue;

	echo $dt->format('j');

	if (floor(($dt->format('j')-1)/7) == 0) {
		echo "am<br />";
	} else if (floor(($dt->format('j')-1)/7) == 2) {
		echo "pm<br />";
		$am = false;

	} else {
		if ($dt->format('n') == 3) {
			echo "am - palm<br />";
		} else {
			if ($dt->format('c') == '2015-11-22T00:00:00-05:00') {
				echo "pm alternate<br />";
				$am = false;
			} else {
				echo $dt->format('c');
			}
		}
	}

	if ($am) {
		$event = new Event("AM Communion " . $dt->format('M Y'));
		$event->name = "AM Communion " . $dt->format('M Y');
		$dt->modify("11:00 AM");
		$event->dt = $dt->getTimestamp();
		$event->grp = 1;
		$event->commit();
	} else {
		$event = new Event("PM Communion " . $dt->format('M Y'));
		$event->name = "PM Communion " . $dt->format('M Y');
		$dt->modify("6:00 PM");
		$event->dt = $dt->getTimestamp();
		$event->grp = 2;
		$event->commit();
	}

	$src = "DB";

	set_time_limit(2);

	$opp = new Opp($event->id, $p->id, $src);
	$opp->event = $event->id;
	$opp->person = $p->id;
	$opp->status = 1;
	$opp->confidence = 90;
	$opp->source = $src;
	$opp->commit();



}

