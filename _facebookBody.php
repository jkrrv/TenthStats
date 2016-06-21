<?php

	require_once 'libs/facebook-php/facebook-php-sdk-v4/src/Facebook/autoload.php';

	require_once '_db.php';


	foreach ($response as $fbEvent) {
		/** @var $fbEvent \Facebook\GraphNodes\GraphNode */
		var_dump($fbEvent->getField('name'));

		$ev = null;

		$fbId = $fbEvent->asArray()['id'];

		$ev = new Event("f/".$fbId);
		$ev->facebookId = (string)$fbId; //the int value is >32 bits, which is the most PHP can handle as an int.
		$ev->name = $fbEvent->getField('name');
		$ev->dt = $fbEvent->getField('start_time')->getTimestamp();

		$n = $ev->name;
		if (stripos($n, "Tenth Saturday Night") === 0) {
			$g = new Grp("Tenth Saturday Night");
			$ev->grp = $g->id;
		} elseif (stripos($n, "Prayer") > 1) {
				$g = new Grp("Prayer");
				$ev->grp = $g->id;
		} elseif (stripos($n, "Equip") === 0) {
			$g = new Grp("Equip");
			$ev->grp = $g->id;
		} elseif (stripos($n, "TCF Grace & Work") > 1) {
			$g = new Grp("TCF Grace & Work");
			$ev->grp = $g->id;
		} else {
			$ev->grp = $default_g->id;
		}
		var_dump($default_g);

		var_dump($ev);

		$ev->commit();


		set_time_limit(10);

		foreach($fbEvent->getField('attending', 'GraphUser') as $attendee) {
			/** @var $fbEvent \Facebook\GraphNodes\GraphNode */
			$a = $attendee->asArray();


			try {
				$p = new Person($a['first_name'] . ',' . $a['last_name']);
			} catch (Exception $e) {
				echo '_____ ' . $a['first_name'] . ' ' . $a['last_name'] . " (skipped due to ambiguity)";
				echo "<br />";
				continue;
			}


			if ($p->id == 0) {
				echo '_____ ' . $a['first_name'] . ' ' . $a['last_name'] . " (skipped because user was not found)";
			} else {
				$p->facebookId = (int)$a['id'];
				$p->commit();

				$src = "Facebook";

				$opp = new Opp($ev->id, $p->id, $src);
				$opp->event = $ev->id;
				$opp->person = $p->id;
				$opp->source = $src;
				$opp->status = 1;
				$opp->confidence = 50;
				$opp->commit();
			}

			echo $p;
			echo "<br />";
			
		}

		foreach($fbEvent->getField('maybe', 'GraphUser') as $attendee) {
			/** @var $fbEvent \Facebook\GraphNodes\GraphNode */
			$a = $attendee->asArray();


			try {
				$p = new Person($a['first_name'] . ',' . $a['last_name']);
			} catch (Exception $e) {
				echo '_____ ' . $a['first_name'] . ' ' . $a['last_name'] . " (skipped due to ambiguity)";
				echo "<br />";
				continue;
			}


			if ($p->id == 0) {
				echo '_____ ' . $a['first_name'] . ' ' . $a['last_name'] . " (skipped because user was not found)";
			} else {
				$p->facebookId = (int)$a['id'];
				$p->commit();

				$src = "Facebook";

				$opp = new Opp($ev->id, $p->id, $src);
				$opp->event = $ev->id;
				$opp->person = $p->id;
				$opp->source = $src;
				$opp->status = 0;
				$opp->confidence = 50;
				$opp->commit();
			}

			echo $p;
			echo "<br />";

		}
	}

	/* handle the result */

