<?php

require_once '_db.php';

global $pdo;

$g = new Grp("TCN Social");
$g = $g->id;

$s = $pdo->query("
SELECT
  min(e.dt) AS firstAppearance,
  p.id AS pid
FROM _people AS p
  JOIN _opportunities AS o ON p.id = o.person
  JOIN _events AS e ON o.event = e.id
WHERE p.dBirth ISNULL
      AND e.grp = $g
GROUP BY o.person");

while(!!($r = $s->fetch(PDO::FETCH_ASSOC))) {
	$p = new Person($r['pid']);
	$p->dBirth = $r['firstAppearance'] - (365.25 * 24 * 60 * 60 * 26.3490);
	$p->commit();

	echo "Updated $p <br />";
}