


<?php

include '_db.php';

global $pdo;

//$q = $pdo->query("
//SELECT P.FirstName, P.LastName, P.latitude, P.longitude, A.LastAttended
//FROM ATClass as A
//  LEFT JOIN People AS P
//    ON (A.FamilyNumber = P.FamilyNumber AND A.IndividualNumber = P.IndividualNumber)
//WHERE A.Class ='Communion Attendance Record'
//	  AND A.LastAttended <> ''
//      AND P.latitude <> ''
//      AND P.longitude <> ''
//      ");


$q = $pdo->query("
SELECT count(*) AS OppCnt, P.id AS pid, L.lat, L.long, L.id AS lid
FROM _opportunities as O
  LEFT JOIN _people AS P
    ON (P.id = O.person)
  LEFT JOIN _locations AS L
    ON (P.address = L.id)
WHERE O.status > 0
  AND L.lat <> ''
  AND O.event > 0
GROUP BY P.id
ORDER BY L.lat, L.long
      ");

?>
[
{
"id" : "document",
"name" : "CZML of Communicants",
"version" : "1.0"
}<?php


$i = 1;
$data = $q->fetch(PDO::FETCH_ASSOC);
while (($data) !== FALSE) {
	set_time_limit(30);

	$people = [];
	$lastNames = [];
	$lids = [];

	$opps = 0;

	while (true) {

		$person = new Person($data['pid']);
		$people[] = $person;
		$lastNames[] = $person->lName;
		$lastNames = array_unique($lastNames);
		$lat = $data['lat'];
		$long = $data['long'];
		$addrId = $lat . $long;
		$lids[] = $data['lid'];
		$lids = array_unique($lids);

		$data = $q->fetch(PDO::FETCH_ASSOC);

		if ($addrId != $data['lat'] . $data['long']) {
			break;
		}

	}

	?>,{
	"id" : "shape<?php echo $i++; ?>",
	"name" : "<?php echo (count($people) > 1 ? implode(", ", $lastNames) : $people[0]) . " (Loc " . implode(", ", $lids) . ")"; ?>",
	"description" : "<div ><?php
	foreach ($people as $p) {
		/** @var $p Person */
		if (count($people) > 1)
			echo "<h3>" . $p . "</h3>";
		echo "Events:";
		echo "<table style=\\\"font-size: 10px;\\\" >";
		foreach ($p->getCalcdOpps() as $o) {
			/** @var $o CalcOpp */
			echo "<tr>";
			echo "<td>";
//			echo
			echo $o->confidence;
			echo "%</td>";
			echo "<td>";
			echo str_replace('"',"&quot;",$o->getEvent());
			echo "</td>";
			echo "</tr>";
			$opps++;
		}
		echo "</table>";
		$gms = $p->getGroupMemberships();
		if (count($gms) > 0) {
			echo "Groups:";
			echo "<table style=\\\"font-size: 10px;\\\" >";
			foreach ($gms as $gm) {
				/** @var $gm GrpMem */
				echo "<tr>";
				echo "<td>";
				echo str_replace('"', "&quot;", $gm->getGroup()->name);
				echo " (since " . date('j M Y', $gm->dJoin) . ")";
				echo "</td>";
				echo "</tr>";
				$opps++;
			}
			echo "</table>";
		} else {
			echo "No Groups.";
		}
	}
	?></div>",
	"position" : {
		"cartographicDegrees" : [<?php echo $long; ?>, <?php echo $lat; ?>, 0]
	},
	"point" : {
		"pixelSize" : <?php echo sqrt(count($people))*10; ?>,
		"color" : {

			"rgba" : <?php
						$oppsDenominator = 24;
						$opps = $opps / count($people);
						echo "[".(($oppsDenominator-$opps)/$oppsDenominator*240).", 240, 0, 200]\n";
					 ?>
		}
	}
}<?php
}

?>
]
