<?php

	require_once 'libs/facebook-php/facebook-php-sdk-v4/src/Facebook/autoload.php';

	require_once '_db.php';


	foreach ($response->getGraphEdge() as $fbEvent) {
		/** @var $fbEvent \Facebook\GraphNodes\GraphNode */
		var_dump($fbEvent->getField('name'));

		$ev = null;

		$fbId = $fbEvent->asArray()['id'];

		$ev = new Event((string)$fbId);
		if ($ev->id == 0) {
			$ev = new Event("f/".$fbId);
		}
		$ev->facebookId = $fbId; //the int value is >32 bits, which is the most PHP can handle as an int.
		$ev->name = $fbEvent->getField('name');
		$ev->dt = $fbEvent->getField('start_time')->getTimestamp();
		$ev->commit();


		set_time_limit(10);


		var_dump($ev);


		foreach($fbEvent->getField('attending', 'GraphUser') as $attendee) {
			/** @var $fbEvent \Facebook\GraphNodes\GraphNode */
			$a = $attendee->asArray();

			
			$p = new Person($a['first_name'] . ',' . $a['last_name']);

			if ($p->id == 0) {
				echo '_____ ' . $a['first_name'] . ' ' . $a['last_name'] . " (skipped)";
			} else {
				$p->facebookId = (int)$a['id'];
				$p->commit();

				$src = "FaceAtt";

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


			$p = new Person($a['first_name'] . ',' . $a['last_name']);

			if ($p->id == 0) {
				echo '_____ ' . $a['first_name'] . ' ' . $a['last_name'] . " (skipped)";
			} else {
				$p->facebookId = (int)$a['id'];
				$p->commit();

				$src = "FaceMaybe";

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

