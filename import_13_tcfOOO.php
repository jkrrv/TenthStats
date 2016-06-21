<?php

require_once '_db.php';

if (($handle = fopen("K:/SkyDrive/Tenth/Data/TCF/1-1s.csv", "r")) !== FALSE) {
	$data = fgetcsv($handle, 1000, ","); // skip header row.
	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

		set_time_limit(3);

		$src = "Manual";

		try {
			$student = new Person($data[0]);
		} catch (Exception $e) {
			echo "! Skipping " . $data[0] . "<br />";
			continue;
		}

		if ($student->id == 0) {
			echo "! Skipping " . $data[0] . "<br />";
			continue;
		}

		$dt = new DateTime($data[3] . " 12:00 PM");

		$eventName = $student . " One-on-One " . $dt->format('j M Y');

		$event = new Event($eventName);
		$event->name = $eventName;
		$event->dt = $dt->getTimestamp();

		$event->commit();

		$opp = new Opp($event->id, $student->id, $src);
		$opp->event = $event->id;
		$opp->person = $student->id;
		$opp->status = 1;
		$opp->confidence = 100;
		$opp->source = $src;
		$opp->commit();

		foreach (explode("/",$data[4]) as $staffName) {
			switch ($staffName) {
				case "Gavin":
					$staff = new Person("Gavin Lymberopoulos");
					break;
				case "Matt":
					$staff = new Person("Matthew Denney");
					break;
				case "Lauren":
					$staff = new Person("Lauren Krause");
					break;
				case "Zach":
					$staff = new Person("Zack Worsham");
					break;
				case "Ben":
					$staff = new Person("Ben Hutton");
					break;
			}
			if ($staff->id == 0)
				throw new Exception($staffName . "Not Found. ");

			$opp = new Opp($event->id, $staff->id, $src);
			$opp->event = $event->id;
			$opp->person = $staff->id;
			$opp->status = 1;
			$opp->confidence = 100;
			$opp->source = $src;
			$opp->commit();
		}

		echo "Added Opp for " . $student . "<br />";





	}
	fclose($handle);
}
?>