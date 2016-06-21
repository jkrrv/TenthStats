<?php

global $pdo;
$pdo = new PDO('sqlite:database.sqlite3');



abstract class Base {
	protected $id = 0;

	protected $_tableName;
	private $_dirty = false;

	public abstract function stringSearch($string);

	public function __construct($id) {
		global $pdo;

		if ($id == null) {
			throw new Exception("the heck!?");
		}

		if (!is_numeric($id) || !is_int($id + 0)) {
			$id = $this->stringSearch($id);
		}

		if ($id != 0) {
			$s = $pdo->prepare("SELECT * FROM " . $this->_tableName . " WHERE id = :id");
			try {
				$s->execute([':id' => $id]);
				foreach ($s->fetch(PDO::FETCH_ASSOC) AS $item => $value) {
					$this->$item = $value;
				}

				$i = 0;
				while ($meta = $s->getColumnMeta($i++)) {
					$fieldName = $meta['name'];
					switch ($meta['native_type']) {
						case 'integer':
							$this->$fieldName = (int)$this->$fieldName;
							break;
						case 'double':
							$this->$fieldName = (double)$this->$fieldName;
							break;
					}

				}

			} catch (Exception $e) {
				$this->id = 0;
			}
		}

	}

	public function getMemberNames() {
		$r = [];
		foreach ($this as $k => $v) {
			if ($k == 'id')
				continue;
			if (substr($k, 0, 1) == '_')
				continue;
			$r[] = $k;
		}
		return $r;
	}


	public function getUpdateStatement() {
		$a = [];
		foreach($this->getMemberNames() as $m) {
			$a[] = $m . " = :" . $m;
		}
		return "UPDATE " . $this->_tableName . " SET " . implode(", ", $a) . " WHERE id=" . $this->id;

	}


	public function getInsertStatement() {
		return "INSERT INTO " . $this->_tableName . " (" . implode(", ", $this->getMemberNames()) . ") VALUES (:" . implode(", :", $this->getMemberNames()) . ")";
	}


	public function commit() {
		if (!$this->_dirty) {
			return 0;
		}

		global $pdo;

		$pdo->beginTransaction();
		if ($this->id == 0) {
			$s = $pdo->prepare($this->getInsertStatement());
		} else {
			$s = $pdo->prepare($this->getUpdateStatement());
		}
		foreach ($this->getMemberNames() as $n) {
			$s->bindValue(":" . $n, $this->$n);
		}

		$ex = $s->execute();

		if ($this->id == 0) {
			$this->id = (int) $pdo->lastInsertId();
		}

		$pdo->commit();
		$this->_dirty = false;

		return $ex;
	}

	public function __get($what) {
		return $this->$what;
	}

	public function __set($what, $value) {
		if ($what == 'id')
			return;
		if ($this->$what != $value) {
			$this->$what = $value;
			$this->_dirty = true;
		}
	}


}


class Location extends Base {
	protected $address;
	protected $state;
	protected $name;
	protected $type;
	protected $lat;
	protected $long;
	protected $accur;

	protected $_tableName = "_locations";

	public function stringSearch($string) {
		global $pdo;

		$s = $pdo->prepare("SELECT id FROM _locations WHERE address = :str OR name = :str");
		try {
			$s->execute([':str' => $string]);
			return $s->fetchColumn(0);
		} catch (Exception $e) {
			return 0;
		}
	}
}

class Grp extends Base {
	protected $id;
	protected $name;
	protected $type;
	protected $gender = 0;

	protected $_tableName = "_grps";

	public function stringSearch($string) {
		global $pdo;

		$s = $pdo->prepare("SELECT id FROM _grps WHERE name = :name");
		try {
			$s->execute([
				':name' => $string
			]);
			return $s->fetchColumn(0);
		} catch (Exception $e) {
			return 0;
		}
	}
}


class GrpMem extends Base {
	protected $id;
	protected $person;
	protected $grp;
	protected $level;
	protected $dJoin;
	protected $dLeft;

	protected $_tableName = "_grpmem";

	public function stringSearch($string) {
		global $pdo;

		if (is_numeric($string)) {
			$string = explode(".", $string);
			$s = $pdo->prepare("SELECT id FROM _grpmem WHERE person = :p AND grp = :g");
			try {
				$s->execute([
					':p' => (int)$string[0],
					':g' => (int)$string[1]
				]);
				return $s->fetchColumn(0);
			} catch (Exception $e) {
				return 0;
			}
		} else {

			throw new Exception("oops");

		}
	}

	public function getGroup() {
		return new Grp($this->grp);
	}
}


class Event extends Base {
	protected $id;
	protected $grp;
	protected $dt;
	protected $location;
	protected $facebookId;
	protected $name;

	protected $_tableName = "_events";

	public function stringSearch($string) {
		global $pdo;

		if (strpos($string,"f/") === 0) {
			$s = $pdo->prepare("SELECT id FROM _events WHERE facebookId = :facebookId");
			try {
				$s->execute([
					':facebookId' => substr($string,2)
				]);
				return $s->fetchColumn(0);
			} catch (Exception $e) {
				return 0;
			}
		} else {
			$s = $pdo->prepare("SELECT id FROM _events WHERE name = :name");
			try {
				$s->execute([
					':name' => $string
				]);
				if ($s->rowCount() > 1) {
					throw new Exception("Event Name is Ambiguous.");
				}
				return $s->fetchColumn(0);
			} catch (Exception $e) {
				return 0;
			}
		}
	}

	public function __toString() {
		return "" . $this->name;
	}
}

class CalcOpp extends Opp {
	public function __construct($person, $event) {
		global $pdo;

		$this->person = $person;
		$this->event = $event;

		$s = $pdo->prepare("SELECT status FROM _opportunities WHERE person = :person AND event = :event AND confidence == 100 ");
		$s->execute([
			':person' => $person,
			':event' => $event
		]);
		if ((!!$status = $s->fetchColumn(0))) {
			$this->status = $status;
			$this->confidence = 100;
			return;
		}
		$numerator = 0;
		$denominator = 0;
		$s = $pdo->prepare("SELECT source AS src, status AS sta FROM _opportunities WHERE person = :person AND event = :event");
		$s->execute([
			':person' => $person,
			':event' => $event
		]);
		while(!!($r = $s->fetch(PDO::FETCH_ASSOC))) {
			$likely = Opp::getAttLikelihoodByPersonAndSourceAndClaim($person, $r['src'], $r['sta']);
			if ($likely !== false) {
				$numerator += (int)$likely;
				$denominator += 1;
			}
		}
		if ($denominator == 0) {
			$s = $pdo->prepare("SELECT source AS src, status AS sta FROM _opportunities WHERE person = :person AND event = :event");
			$s->execute([
				':person' => $person,
				':event' => $event
			]);
			while(!!($r = $s->fetch(PDO::FETCH_ASSOC))) {
				$likely = Opp::getAttLikelihoodBySourceAndClaim($r['src'], $r['sta']);
				if ($likely !== false) {
					$numerator += (int)$likely;
					$denominator += 1;
				}
			}
		}
		if ($denominator == 0) {
			$this->status = 0;
			$this->confidence = 50;
		} else {
			$likely = $numerator / $denominator;
			$this->status = ($likely < 50 ? -1 : 1);
			$this->confidence = max($likely, 100 - $likely);
		}


		return;
	}

	public function commit() {
		throw new Exception("Can't Commit a CalcOpp object.");
	}
}


class Opp extends Base {
	protected $id;
	protected $person;
	protected $event;
	protected $status;
	protected $confidence;
	protected $source;

	protected $_tableName = '_opportunities';

	public function __construct($event, $person, $source = null) {
		global $pdo;

		$id = 0;

		if ($person === null) {
			$id = $event;
		} else if ($source != null) {
			$s = $pdo->prepare("SELECT id FROM _opportunities WHERE event = :event AND person = :person AND source = :source");
			try {
				$s->execute([
					':event' => (int)$event,
					':person' => (int)$person,
					':source' => $source
				]);
				$id = $s->fetchColumn(0);
			} catch (Exception $e) {
				$id = 0;
			}
		} else if ($id == 0) {
			$s = $pdo->prepare("SELECT id FROM _opportunities WHERE event = :event AND person = :person");
			try {
				$s->execute([
					':event' => (int)$event,
					':person' => (int)$person
				]);
				$id = $s->fetchColumn(0);
			} catch (Exception $e) {
				$id = 0;
			}
		}

		if ($id == 0) {
			$id = ' ';
		}

		parent::__construct($id);
	}

	public function stringSearch($string) {
		if ($string === ' ')
			return 0;
		throw new Exception("Unexpected constructor arguments.");
	}

	public function getEvent() {
		return new Event($this->event);
	}
	
	public static function getAttLikelihoodByPersonAndSourceAndClaim($person, $src, $claim) {
		global $pdo;
		$s = $pdo->prepare("
			SELECT
			  sum(authority.status == 1) * 100 / count(authority.id) AS pctAttend
			
			FROM _opportunities AS authority
			  JOIN _opportunities AS lower ON authority.confidence = 100
					AND lower.confidence < 100
					AND authority.person = lower.person
					AND authority.event = lower.event
					AND lower.confidence > 0
			WHERE authority.person = :person
					AND lower.source = :source
					AND lower.status = :claim
			GROUP BY lower.source;
		");
		$s->execute([':person' => $person,
					 ':source' => $src,
					 ':claim' => $claim]);

		return $s->fetchColumn(0);
	}

	public static function getAttVarianceByPersonAndSourceAndClaim($person, $src, $claim) {
		global $pdo;
		$mu = self::getAttLikelihoodByPersonAndSourceAndClaim($person, $src, $claim) * 100;
		$s = $pdo->prepare("
			SELECT
			  sum((((authority.status == 1) * 100) - :mu) * (((authority.status == 1) * 100) - :mu)) / count(authority.id) AS variance
			
			FROM _opportunities AS authority
			  JOIN _opportunities AS lower ON authority.confidence = 100
					AND lower.confidence < 100
					AND authority.person = lower.person
					AND authority.event = lower.event
					AND lower.confidence > 0
			WHERE authority.person = :person
					AND lower.source = :source
					AND lower.status = :claim
			GROUP BY lower.source;
		");
		$s->execute([
			':source' => $src,
			':claim' => $claim,
			':mu' => $mu
		]);

		return $s->fetchColumn(0) / (100 * 100);
	}

	public static function getAttLikelihoodBySourceAndClaim($src, $claim) {
		global $pdo;
		$s = $pdo->prepare("
			SELECT
			  sum(authority.status == 1) * 100 / count(authority.id) AS pctAttend
			
			FROM _opportunities AS authority
			  JOIN _opportunities AS lower ON authority.confidence = 100
					AND lower.confidence < 100
					AND authority.person = lower.person
					AND authority.event = lower.event
					AND lower.confidence > 0
			WHERE lower.source = :source
				AND lower.status = :claim
			GROUP BY lower.source;
		");
		$s->execute([':source' => $src,
					 ':claim' => $claim]);

		return $s->fetchColumn(0);
	}

	public static function getAttVarianceBySourceAndClaim($src, $claim) {
		global $pdo;
		$mu = self::getAttLikelihoodBySourceAndClaim($src, $claim) * 100;
		$s = $pdo->prepare("
			SELECT
			  sum((((authority.status == 1) * 100) - :mu) * (((authority.status == 1) * 100) - :mu)) / count(authority.id) AS variance
			
			FROM _opportunities AS authority
			  JOIN _opportunities AS lower ON authority.confidence = 100
					AND lower.confidence < 100
					AND authority.person = lower.person
					AND authority.event = lower.event
					AND lower.confidence > 0
			WHERE lower.source = :source
				AND lower.status = :claim
			GROUP BY lower.source;
		");
		$s->execute([
			':source' => $src,
			':claim' => $claim,
			':mu' => $mu
		]);

		return $s->fetchColumn(0) / (100 * 100);
	}
}


class Person extends Base {
	protected $id;
	protected $familyNum;
	protected $indivNum;
	protected $familyRole;
	protected $fName;
	protected $mName;
	protected $lName;
	protected $pName;
	protected $tName;
	protected $address;
	protected $gender;
	protected $marital;
	protected $memStatus;
	protected $dBirth;
	protected $dJoined;
	protected $parish;
	protected $facebookId;
	

	protected $_tableName = "_people";

	public function stringSearch($string) {
		global $pdo;
		
		if (is_numeric($string)) {
			$string = explode(".", $string);
			$s = $pdo->prepare("SELECT id FROM _people WHERE familyNum = :fNum AND indivNum = :iNum");
			try {
				$s->execute([
					':fNum' => (int)$string[0],
					':iNum' => (int)$string[1]
				]);
				return $s->fetchColumn(0);
			} catch (Exception $e) {
				return 0;
			}
		} else {
			$st = explode(",", $string);

			if (count($st) != 2)
				$st = explode(" ", $string);

			if (count($st) != 2)
				throw new Exception('unexpected number of names : ' . $string);

			$s = $pdo->prepare("SELECT id FROM _people WHERE upper(lName) = upper(:lName) AND (upper(fName) = upper(:fName) OR upper(pName) = upper(:fName))");
			$c = $pdo->prepare("SELECT COUNT(*) FROM _people WHERE upper(lName) = upper(:lName) AND (upper(fName) = upper(:fName) OR upper(pName) = upper(:fName))");
			try {
				$s->execute([
					':fName' => $st[0],
					':lName' => $st[1]
				]);
				$c->execute([
					':fName' => $st[0],
					':lName' => $st[1]
				]);
			} catch (Exception $e) {
				return 0;
			}


			$resultCount = $c->fetchColumn(0);
			if ($resultCount > 1) { // many results.
				throw new Exception("Ambiguous Records : " . $string . " matches multiple people");
			}
			if ($resultCount == 1) { // one result
				return $s->fetchColumn(0);
			}


			// no results.  Let's try with nicknames. 
			
			$s = $pdo->prepare("SELECT id FROM _people as p JOIN '-nicknames' AS n ON p.fName = n.first WHERE upper(lName) = upper(:lName) AND upper(nick) = upper(:fName)");
			$c = $pdo->prepare("SELECT COUNT(*) FROM _people as p JOIN '-nicknames' AS n ON p.fName = n.first WHERE upper(lName) = upper(:lName) AND upper(nick) = upper(:fName)");
			try {
				$s->execute([
					':fName' => $st[0],
					':lName' => $st[1]
				]);
				$c->execute([
					':fName' => $st[0],
					':lName' => $st[1]
				]);
			} catch (Exception $e) {
				return 0;
			}

			$resultCount = $c->fetchColumn(0);
			if ($resultCount > 1) { // many results.
				throw new Exception("Ambiguous Records : " . $string . " matches multiple people");
			}
			if ($resultCount == 1) { // one result
				return $s->fetchColumn(0);
			}

			return 0;
		}
	}

	public function getPrefName() {
		if ($this->pName != null)
			return $this->pName;
		return $this->fName;
	}

	public function __toString() {
		return $this->getPrefName() . " " . $this->lName;
	}


	public function getOppsTaken() {
		global $pdo;

		$opps = [];

		$s = $pdo->prepare("
			SELECT o.id 
			FROM _opportunities AS o 
			  JOIN _events AS e
			  	ON o.event = e.id
		 	WHERE person = :person AND 
		 		status > 0 
			ORDER BY e.dt DESC, o.confidence DESC");
		$s->execute([
			':person' => $this->id
		]);


		while (($o = $s->fetchColumn(0)) !== false) {
			$opps[] = new Opp($o, null);
		}

		return $opps;

	}


	public function getCalcdOpps() {
		global $pdo;

		$opps = [];

		$s = $pdo->prepare("
			SELECT e.id AS eid
			FROM _opportunities AS o 
			  JOIN _events AS e
			  	ON o.event = e.id
		 	WHERE person = :person AND 
		 		status > 0 
			GROUP BY e.id 
			ORDER BY e.dt DESC
			");
		$s->execute([
			':person' => $this->id
		]);

		while (($e = $s->fetchColumn(0)) !== false) {
			$opps[] = new CalcOpp($this->id, $e);

		}

		return $opps;
	}


	public function getGroupMemberships() {
		global $pdo;

		$gms = [];

		$s = $pdo->prepare("
			SELECT gm.id 
			FROM _grpmem AS gm  
		 	WHERE gm.person = :person");
		$s->execute([
			':person' => $this->id
		]);


		while (($gm = $s->fetchColumn(0)) !== false) {
			$gms[] = new GrpMem($gm);
		}

		return $gms;

	}
}

