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
			try {
				$s->execute([
					':fName' => $st[0],
					':lName' => $st[1]
				]);
			} catch (Exception $e) {
				return 0;
			}

			if ($s->rowCount() > 1) {
				throw new Exception("Ambiguous Records : matches multiple people");
			}
			return $s->fetchColumn(0);

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
}

