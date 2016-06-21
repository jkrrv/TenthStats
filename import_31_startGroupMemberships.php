<?php
require_once '_db.php';

global $pdo;

$s = $pdo->query("
SELECT 
	o.person AS pid,
	e.grp AS gid,
	min(e.dt) AS dFirst
FROM _opportunities AS o 
	JOIN _events AS e ON o.event = e.id
WHERE e.grp NOTNULL 
GROUP BY o.person, e.grp");


while(($r = $s->fetch(PDO::FETCH_ASSOC)) !== false) {
	set_time_limit(5);

	$gm = new GrpMem($r['pid'] . '.' . $r['gid']);
	$gm->person = $r['pid'];
	$gm->grp = $r['gid'];

	$gm->level = ($gm->level | 1);

	if ($gm->dJoin == null) {
		$gm->dJoin = $r['dFirst'];
	} else {
		$gm->dJoin = min($gm->dJoin, $r['dFirst']);
	}
	$gm->commit();

	echo "Updated Group Membership #" . $gm->id . "<br />";

}

