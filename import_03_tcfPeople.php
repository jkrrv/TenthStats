<?php

require_once '_db.php';

if (($handle = fopen("K:/SkyDrive/Tenth/Data/TCF/people.csv", "r")) !== FALSE) {
	
	$data = fgetcsv($handle, 1000, ","); // skip first row
	$data = fgetcsv($handle, 1000, ","); // skip second row
	
	while(($data = fgetcsv($handle, 1000, ",")) !== false) {
		if ($data[0] == '' || $data[1] == '') {
			echo "Skipping " . $data[0] . " " . $data[1] . " due to lack of second name. <br />";
			continue;
		}


		try {
			$p = new Person($data[0] . "," . $data[1]);
		} catch (Exception $e) {
			echo $e->getMessage() . "<br />";
		}

		
		if ($p->dBirth == null) {
			switch (strtolower($data[4])) {
				case "senior":
					$p->dBirth = 757400400; // jan 1 1994
					break;
				case "junior":
					$p->dBirth = 788936400; // jan 1 1995
					break;
				case "sophomore":
					$p->dBirth = 820472400; // jan 1 1996
					break;
				case "freshman":
					$p->dBirth = 852094800; // jan 1 1997
					break;
			}
		}

		if ($p->id != null) {
			echo "Skipping " . $data[0] . " " . $data[1] . " because they already exist here. <br />";
			$p->commit();
			continue;
		}

		echo "Importing " . $data[0] . " " . $data[1] . "... <br />";

		$p->fName = $data[0];
		$p->pName = $data[0];
		$p->lName = $data[1];

		switch (strtolower($data[3])) {
			case "male":
				$p->gender = 1;
				break;
			case "female":
				$p->gender = 2;
				break;
		}

		$p->commit();
	}

	
	
}