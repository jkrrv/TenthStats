<?php

// headers for making this an 'm file'


require_once '_db.php';

global $pdo;

$s = $pdo->query("SELECT p.dBirth, SUM(o.status > 0), COUNT(o.status) FROM _people AS p JOIN _opportunities AS o ON p.id = o.person WHERE p.dBirth NOT NULL GROUP BY o.person");

$matlabArray1 = [];
$matlabArray2 = [];

while (($r = $s->fetch(PDO::FETCH_NUM)) !== FALSE) {
	if ($r[1] == 0)
		continue;

	$matlabArray1[] = $r[0] / (365.25 * 24 * 60 * 60) + 1970;
	$matlabArray2[] = $r[1] / $r[2];
}



ob_start();

echo "yBirth = [" . implode(' ', $matlabArray1) . "];\n\n";

echo "attFreq = [" . implode(' ', $matlabArray2) . "];\n\n";

?>
	figure(1);
	histfit(yBirth);
	title('Distribution of Dates of Birth of Attendees')

	figure(2);
	hist(attFreq, .05:.1:.95);
	title('Frequency of Attendance')

	figure(3);
	scatter(yBirth, attFreq);

<?php


// AGE OF ENTRY TO TCN


$g = new Grp("TCN Social");
$g = $g->id;

$s = $pdo->query("
SELECT 
	min(e.dt) - p.dBirth AS timeoffset
FROM _people AS p 
	JOIN _opportunities AS o ON p.id = o.person 
	JOIN _events AS e ON o.event = e.id
WHERE p.dBirth NOT NULL 
	AND e.grp = $g
GROUP BY o.person");

unset($matlabArray1, $matlabArray2);
$matlabArray1 = [];

while (($r = $s->fetch(PDO::FETCH_NUM)) !== FALSE) {

	$matlabArray1[] = $r[0] / (365.25 * 24 * 60 * 60);
}


echo "firstTCN = [" . implode(' ', $matlabArray1) . "];\n\n";

?>
	figure(4);
	hist(firstTCN);
	title('Age at First TCN Event');

	% Before TCN Age Speculation
	% firstTCN = [65.277292950034 49.009639516313 19.958447410449 33.255475701574 26.63620807666 33.389630390144 35.460586356377 27.838295687885 63.327943189596 34.075119780972 37.979409080538 29.780857859913 28.015143737166 29.471366643851 28.3873773671 23.166210358202 28.447838238649 24.971765913758 30.157255304586 33.912474332649 26.515856719142 37.373802190281 27.185574948665 31.432694501483 28.571868583162 25.488715871929 26.349018936801 25.385295459731 30.142254163815 30.450319415925 24.859342915811 28.034907597536 29.716005019393 24.653547798312 26.930954825462 26.753479352042 22.608373260324 27.255076431668 30.351756787588 52.349275610313 27.870978781656 26.567647729865 26.895362765229 27.290326260552 28.191088675945 52.801020990189 26.347792607803 63.224389687429 28.599133013917 37.223362993384 36.286190965092 35.12913529546 58.186259411362 25.272872461784 23.16746520648 25.475644535706 26.90063883185 23.695642254164 26.731804699977 31.763375541866 31.369838010495 18.564168377823 24.522444672599 25.43691535478 22.941906228611 23.169746748802 22.507643166781 22.832991101985 19.270533880903 21.954654346338 25.466917636322 26.681696326717 23.724731918777 22.753479352042 23.582449235683 24.604608715492 22.793948208989 21.474503764545 29.248003650468 27.799566506959 25.148414328086 23.689225416381 22.321070043349 29.587611225188 29.280629705681 26.490759753593 26.657911248004 22.147929500342 23.654346338125 24.407369381702 21.871292493726 22.405886379192 27.144507186858 25.617271275382 35.64356605065 23.758384668036 25.611795573808 22.745265799681 26.077344284736 25.830823636778 22.496349532284 24.765200775724 29.995208761123 31.11233744011 22.96107118412 25.204083960757 25.116358658453 18.909023499886 24.982089892768 21.340349075975 23.57517681953 21.918434861967 30.124058863792 20.284166096281 31.678416609628 21.951517225645 21.664099931554 22.896075747205 27.640371891399 24.428986995209 24.869666894821 23.049395391285 25.25855578371 28.500627424139 26.345767738992 23.563997262149 26.510038786219 29.370807665982 23.032911248004 24.16096281086 23.271503536391 27.90947980835 25.36322153776 53.794860825918 37.594997718458];

	median(firstTCN)  % before TCN age speculation: 26.3490


<?php




// OPPS FOR PEOPLE



$s = $pdo->query("
SELECT 
	count(*) AS count
	FROM _opportunities  
GROUP BY person");

unset($matlabArray1, $matlabArray2);
$matlabArray1 = [];

while (($r = $s->fetch(PDO::FETCH_NUM)) !== FALSE) {
	$matlabArray1[] = $r[0];
}


echo "oppCount = [" . implode(' ', $matlabArray1) . "];\n\n";

?>
	figure(5);
	histfit(oppCount);
	title('Opp Count');

<?php




// OPPS FOR PEOPLE



$s = $pdo->query("
SELECT 
	count(*) AS count
	FROM _opportunities  
	WHERE status == 1
GROUP BY person");

unset($matlabArray1, $matlabArray2);
$matlabArray1 = [];

while (($r = $s->fetch(PDO::FETCH_NUM)) !== FALSE) {
	$matlabArray1[] = $r[0];
}


echo "oppCount = [" . implode(' ', $matlabArray1) . "];\n\n";

?>
	figure(6);
	histfit(oppCount);
	title('Opp Count');


<?php

// JAMES'S OPPS OVER TIME



$s = $pdo->query("
SELECT 
	_events.dt / (365.25 * 24 * 60 * 60) + 1970  AS d,
	_opportunities.status * _opportunities.confidence / 100 AS s
	FROM _opportunities 
	JOIN _events ON _opportunities.event = _events.id 
	WHERE person = 7309 AND
		_events.dt / (365.25 * 24 * 60 * 60) + 1970 > 2015.7
	group by _events.id 
	order by d;
");

unset($matlabArray1, $matlabArray2);
$matlabArray1 = [2014];
$matlabArray2 = [0];
$matlabArray3 = [0];
$previousStatus = 0;

while (($r = $s->fetch(PDO::FETCH_NUM)) !== FALSE) {
	$matlabArray1[] = $r[0];
	$previousStatus += $r[1];
	$matlabArray3[] = $r[1];
	$matlabArray2[] =  $previousStatus;
}


echo "oppDates = [" . implode(' ', $matlabArray1) . "];\n\n";
echo "statuses = [" . implode(' ', $matlabArray2) . "];\n\n";
echo "dStatuses = [" . implode(' ', $matlabArray3) . "];\n\n";

?>
	figure(7);
	plot(oppDates, statuses);
	xlabel('Date (Year)');
	ylabel('Engagement Score');

	figure(8);
	plot(oppDates, dStatuses);
	xlabel('Date (Year)');
	ylabel('Derivative of Engagement Score');


<?php


file_put_contents('analysis.m', ob_get_contents());

echo ob_get_clean();

