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

		if ($this->id == 0) {
			$pdo->beginTransaction();
			$s = $pdo->prepare($this->getInsertStatement());
			$this->id = (int) $pdo->lastInsertId();
			$pdo->commit();
		} else {
			$s = $pdo->prepare($this->getUpdateStatement());
		}
		foreach ($this->getMemberNames() as $n) {
			$s->bindValue(":" . $n, $this->$n);
		}
		$this->_dirty = false;
		return $s->execute();
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

	protected $tableName = "_grps";

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

			throw new Exception("oops");

			global $pdo;

			$s = $pdo->prepare("SELECT id FROM _people WHERE address = :str OR name = :str");
			try {
				$s->execute([':str' => $string]);
				return $s->fetchColumn(0);
			} catch (Exception $e) {
				return 0;
			}


		}
	}
}

