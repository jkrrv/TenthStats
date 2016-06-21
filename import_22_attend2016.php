<?php


function wkOrdinal($number) {
	switch($number) {
		case 0:
			return "first";
		case 1:
			return "second";
		case 2:
			return "third";
		case 3:
			return "fourth";
		case 4:
			return "fifth";
	}
	throw new Exception("Not a valid number");
}

require_once '_db.php';

$dtStart = new DateTime();


if (($handle = fopen("K:/SkyDrive/Tenth/Data/DbAttend/Communion2016.csv", "r")) !== FALSE) {
	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		if ($data[0] == '') {
			if (stripos($data[4], 'Report Date') === 0) {
				$datesNStuff = explode(" ", $data[4]);
				$dtStart = new DateTime($datesNStuff[2]);
				$dtEnd = min(new DateTime($datesNStuff[4]), new DateTime());
			}
			continue;
		}

		if ($data[0] == 'Time :')
			continue;
		if ($data[0] == 'Date :')
			continue;
		if ($data[0] == 'Class:')
			continue;
		if ($data[0] == 'Name')
			continue;
		if ($data[0] == '*=Non-Enrolled')
			continue;


		var_dump($data);


		$s = $pdo->prepare("SELECT FamilyNumber, IndividualNumber FROM People WHERE IndividualLabelName = :iln");
		$s->execute([':iln' => $data[0]]);

		$pStr = $s->fetch(PDO::FETCH_NUM);
		if (count($pStr) < 2)
			continue;
		$pStr = implode(".", $pStr);


		$p = new Person((string)$pStr);

		if ($p->id == 0) {
			echo "Person not imported (" . $data[0] . ")<br />";
			continue;
		}


		for ($col = 3; $col < 15; $col++) {


			$month = new DateTime($dtStart->format(DateTime::ISO8601));
			$month = $month->modify("+" . $col-3 . " months");

			$d = $data[$col] . "      ";
			for ($wk = 0; $wk < 5; $wk++) {
				if ($d[$wk] != ' ') {


					$dt = new DateTime(wkOrdinal($wk) . " Sunday of " . $month->format('F Y'));

					$am = true;


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
								echo "!!!!!  " . $dt->format('c');
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

					$src = "DBAtt";

					set_time_limit(2);

					$opp = new Opp($event->id, $p->id, $src);
					$opp->event = $event->id;
					$opp->person = $p->id;
					if ($d[$wk] == "A") {
						$opp->status = -1;
						$opp->confidence = 80;
					} elseif ($d[$wk] == "P") {
						$opp->status = 1;
						$opp->confidence = 90;
					}
					$opp->source = $src;
					$opp->commit();
				}
			}

		}
		echo "<br />";
		

	}
	fclose($handle);
}