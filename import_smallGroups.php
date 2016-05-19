<?php

include '_db.php';

global $pdo;

// make sure larger groups exist.
$g = new Grp("Communion AM");
$g->name = "Communion AM";
$g->type = "Commu";
$g->commit();

$g = new Grp("Communion PM");
$g->name = "Communion PM";
$g->type = "Commu";
$g->commit();

$g = new Grp("Prayer");
$g->name = "Prayer";
$g->type = "Prayer";
$g->commit();


$g = new Grp("Tenth Saturday Night");
$g->name = "Tenth Saturday Night";
$g->type = "Large";
$g->commit();

$g = new Grp("TCN Social");
$g->name = "TCN Social";
$g->type = "Large";
$g->commit();

$g = new Grp("TCF Sunday");
$g->name = "TCF Sunday";
$g->type = "Sunday";
$g->commit();



$s = $pdo->prepare("SELECT GroupName FROM SGRoster WHERE SGRoster.GroupActive = 'True' AND SGRoster.CloseDate = '' GROUP BY GroupName;");

$s->execute();

while ($grp = $s->fetchColumn(0)) {

	$g = new Grp($grp);
	$g->name = $grp;
	$g->type = "Small";

	if (stripos($grp, "Women") !== false) {
		$g->gender = 2;
	} else if (stripos($grp, "Men") !== false) {
		$g->gender = 1;
	}
	
	$g->commit();

	echo "imported " . $grp . "<br />";
}

echo "<p>Done importing Small Group names.</p>";





$s = $pdo->prepare("SELECT GroupName as gn, FamilyNumber as fn, Position as po, IndividualNumber as inn, DateAdded as da, DateRemoved as dr FROM SGRoster WHERE SGRoster.GroupActive = 'True' AND SGRoster.CloseDate = '';");
$s->execute();

while ($r = $s->fetch(PDO::FETCH_ASSOC)) {

	set_time_limit(2);

	$g = new Grp($r['gn']);
	$p = new Person($r['fn'] . "." . $r['inn']);
	if ($g->id == 0) {
		throw new Exception("$g->name");
	}
	if ($p->id == 0) {
		continue;
	}
	$m = new GrpMem($p->id . '.' . $g->id);
	$m->person = $p->id;
	$m->grp = $g->id;
	if ($r['da'] != null) {
		$m->dJoin = strtotime($r['da']);
	}
	if ($r['dr'] != null) {
		$m->dLeft = strtotime($r['dr']);
	}
	switch ($r['po']) {
		case "Member":
			$m->level = ($m->level | 1);
			break;
		case "Host":
			$m->level = ($m->level | 2);
			break;
		case "Leader":
			$m->level = ($m->level | 4);
			break;
		default:
			throw new Exception($r['po']);
	}
	$m->commit();

//	var_dump($m);

	echo "imported " . $p->fName . " " . $p->lName . "<br />";
}

echo "<p>Done importing Small Group members.</p>";