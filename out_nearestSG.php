<style>

	td {
		padding: .5em;
	}

	tr:nth-child(odd) {
		background: #eee;
	}
</style>


<?php


$sgjson = file_get_contents("https://tenth.gospel.io/smallgroups/json");

$sgjson = json_decode($sgjson);


foreach ($sgjson->groups as &$sg) {
	if ($sg == null)
		continue;

	@$sg->gender = 0;
	if (stripos($sg->title, "Women") !== false || stripos($sg->title, "Moms") !== false) {
		$sg->gender = 2;
	} else if (stripos($sg->title, "Men") !== false) {
		$sg->gender = 1;
	}
}

$sgLists = [];


function calculateDistance($a, $b) {
	
        $R = 3958.761; // miles
        $p1 = radians($a->lat);
        $p2 = radians($b->lat);
        $dp = radians($b->lat - $a->lat);
        $dl = radians($b->lng - $a->lng);
        $f = sin($dp/2) * sin($dp/2) +
             cos($p1) * cos($p2) * sin($dl/2) * sin($dl/2);
        $c = 2 * atan2(sqrt($f), sqrt(1-$f));

    return $R * $c;
}

function radians($degrees) {
	return $degrees / 180 * pi();
}

include_once '_db.php';


$q = $pdo->query("
SELECT P.id AS pid, L.lat, L.long, L.id AS lid, P.memStatus AS memStatus, COUNT(O.id) AS OppCnt
FROM _people AS P
  LEFT JOIN _locations AS L
    ON (P.address = L.id)
  LEFT JOIN _opportunities AS O
    ON (O.person = P.id)
WHERE L.lat <> ''
GROUP BY P.id
ORDER BY L.lat, L.long
      ");


$i = 1;
$data = $q->fetch(PDO::FETCH_ASSOC);
while (($data) !== FALSE) { // for each address (-ish)
	set_time_limit( 30 );

	if ($data['OppCnt'] === 0 && stripos($data['memStatus'], "Member") === false)
		continue;

	$people    = [];
	$lastNames = [];
	$lids      = [];
	$parishes  = [];

	$members = 0;
	$opps = 0;

	while ( true ) { // group together all people at the address.

		$person      = new Person( $data['pid'] );
		$people[]    = $person;
		$lastNames[] = $person->lName;
		$parishes[]  = $person->parish;
		$lat         = $data['lat'];
		$long        = $data['long'];
		$addrId      = $lat . $long;
		$lids[]      = $data['lid'];


		$opps +=    $data['OppCnt'];
		$members += (stripos($data['memStatus'], 'member') === false ? 0 : 1);

		$data = $q->fetch( PDO::FETCH_ASSOC );

		if ( $addrId != $data['lat'] . $data['long'] ) {
			break;
		}

	}


	if ($members === 0 && $opps === 0)
		continue;

	$lids        = array_unique( $lids );
	$lastNames   = array_unique( $lastNames );
	$parishes    = array_unique( $parishes );

	$closestSGs = [];

	foreach ($sgjson->groups as &$sg) {
		if ($sg == null)
			continue;

		$d = calculateDistance($sg->loc, (object)['lat' => $lat, 'lng' => $long]);


		$max = 5;
		for ($i = 0; $i < $max; $i++) {
			if (count($closestSGs) <= $i || $d < $closestSGs[$i]->d) {
				if ($i < $max-1 && isset($closestSGs[$i])) {
					$closestSGs[] = $closestSGs[$i];
				}
				$closestSGs[$i] = (object)['d'=>$d, 'g'=>&$sg];
				break;
			}
		}

	}

	if ($closestSGs[0]->d > 20) // we can skip people more than 20 miles away.
		continue;


	foreach ($people as $p) {

		// skip minors
		if ($p->age() < 18)
			continue;

		foreach($closestSGs as $rank => $pair) {
			$g = $pair->g;

			if (stripos($g->title, "Maranatha") !== false)
				continue;

			if (stripos($g->title, "ESL ") !== false)
				continue;

			if (!isset($sgLists[$g->link]))
				$sgLists[$g->link] = [];

			// skip wrong genders
			if ($g->gender > 0 && $g->gender !== $p->gender)
				continue;

			$sgLists[ $g->link ][] = (object) [ 'd' => $pair->d, 'p' => $p ];
//			echo "! Assigning $p to " . $g->title;
			break;
		}
	}



	?>

	<h2><?php echo ( count( $people ) > 1 ? implode( ", ", $lastNames ) : $people[0] ); ?></h2>

	<?php

	echo "Parish " . implode( ", ", $parishes ) . "<br />";

	echo "$members Members, $opps KOs (Loc " . implode( ", ", $lids ) . ")";

	echo "<h3>Closest Small Groups</h3><ul>";
	for ($i = 0; $i < 3; $i++) {
		echo "<li>{$closestSGs[$i]->g->title} (" . round($closestSGs[$i]->d, 2) . " mi)</li>";
	}
	echo "</ul>";

	echo "<h3>Individuals</h3>";

	foreach ( $people as $p ) {
		/** @var $p Person */

		echo "<h4>$p ({$p->memStatus}, " . $p->age() . ")</h4>";

		echo implode(', ', $p->getPhone()) . "<br />";
		echo implode(', ', $p->getEmails()) . "<br />";

		$ops = $p->getCalcdOpps();
		if (count($ops) > 0) {
			echo "<h5>Seen At:</h5>";
			echo "<table>";
			foreach ( $ops as $o ) {
				/** @var $o CalcOpp */
				echo "<tr>";
				echo "<td>";
//			echo
				echo $o->confidence;
				echo "%</td>";
				echo "<td>";
				echo str_replace( '"', "&quot;", $o->getEvent() );
				echo "</td>";
				echo "</tr>";
				$opps ++;
			}
			echo "</table>";
		}
		$gms = $p->getGroupMemberships();
		if ( count( $gms ) > 0 ) {
			echo "<h5>Group Memberships:</h5>";
			echo "<table>";
			foreach ( $gms as $gm ) {
				/** @var $gm GrpMem */
				echo "<tr>";
				echo "<td>";
				echo str_replace( '"', "&quot;", $gm->getGroup()->name );
				echo " (since " . date( 'j M Y', $gm->dJoin ) . ")";
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}

	ob_flush();
	flush();

	echo "<hr />";
}

echo "<h1>Small Group Target Lists</h1>";


foreach ($sgjson->groups as $group) {
	if ($group == null)
		continue;

	echo "<h2>{$group->title}</h2>";

	if (!isset($sgLists[$group->link])) {
		echo "No people have been assigned to this group.  This is probably but not necessarily intentional.";
		continue;
	}

	echo "<h3>Closest People (" . count($sgLists[$group->link]) . ")</h3>";
	echo "<table>";
	foreach ($sgLists[$group->link] as $s) {
		echo "<tr>";
		echo "<td>" . $s->p . "</td>";
//		echo "<td>"; var_dump($s->p); echo "</td>";
		echo "<td>" . round($s->d, 2) . " mi</td>";
		echo "<td>" . $s->p->age() . " yrs</td>";
		echo "<td>" . implode('<br />', $s->p->getEmails()) . "</td>";
		echo "<td>" . implode('<br />', $s->p->getPhone()) . "</td>";
		echo "</tr>";
	}
	echo "</table>";

	ob_flush();
	flush();



}

?>





