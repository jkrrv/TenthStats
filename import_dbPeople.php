<?php


include '_db.php';


global $pdo;

$q = $pdo->query("SELECT * FROM People WHERE (
					People.DateLastAttended <> '' OR 
					People.DateLastContributed <> '' OR 
					People.EntryDate LIKE '%/201%' OR 
					People.DateLastChanged LIKE '%/201%') 
					AND People.IndReasonDeactivated = ''
					AND People.IndDateDeactivated = ''");



while ($r = $q->fetch(PDO::FETCH_ASSOC)) {

	set_time_limit(2);

	// SKIP NON-PERSON ENTITIES

	if ($r['FirstName'] == null)
		continue;

	if ($r['FirstName'] == "The Estate Of")
		continue;

	if ($r['LastName'] == 'The Matching Gift')
		continue;

	if ($r['LastName'] == 'DUPLICATE')
		continue;

	if ($r['IndividualNumber'] > 40)
		continue;


	// ADDRESS

	$addressString = $r['Address1'] . ($r['Address1'] == '' ? '': ', ') . $r['Address2'] . ($r['Address2'] == '' ? '': ', ') . $r['CityStateZIP-Formatted'];
	$addressId = null;

	if (!!strpbrk($addressString, 'aeiouy1234567890') && $addressString != 'no current address, ') {
		$address = new Location($addressString);
		$address->state = $r['State'];
		$address->address = $addressString;
		$address->type = ($r['AddressType'] == 'Home' ? 1 : 0);
		($r['latitude'] != null ? $address->lat = $r['latitude'] : null);
		($r['longitude'] != null ? $address->long = $r['longitude'] : null);
		($r['precision'] != null ? $address->accur = $r['precision'] : null);
		$address->commit();
		$addressId = $address->id;
	}

	// PERSON
	$person = new Person($r['FamilyNumber'] . "." . $r['IndividualNumber']);
	$person->address = $addressId;
	$person->familyNum = $r['FamilyNumber'];
	$person->indivNum = $r['IndividualNumber'];
	switch($r['IndividualNumber']) {
		case 1:
			$person->familyRole = 1;
			break;


		case 11:
			$person->familyRole = 2;
			break;


		case 21:
		case 22:
		case 23:
		case 24:
		case 25:
		case 26:
		case 27:
		case 28:
		case 29:
		case 30:
			$person->familyRole = 3;
			break;


		default:
			throw new Exception($r['IndividualNumber']);
	}
	$person->fName = $r['FirstName'];
	$person->lName = $r['LastName'];
	$person->mName = $r['MiddleName'];
	$person->pName = $r['GoesByName'];
	$person->tName = $r['Title'];
	switch ($r['Gender']) {
		case "Male":
			$person->gender = 1;
			break;
		case "Female":
			$person->gender = 2;
			break;
		case "":
			break;
		default:
			throw new Exception($r['gender']);
	}
	$person->marital = $r['MaritalStatus'];
	$person->memStatus = $r['MemberStatus'];
	if ($r['DateOfBirth'] != '')
		$person->dBirth = strtotime($r['DateOfBirth']);
	if ($r['DateJoined'] != '')
		$person->dJoined = strtotime($r['DateJoined']);
	switch ($r['IndParish']) {
		case "PARISH 20":
			$person->parish = 20;
			break;
		case "PARISH 1":
			$person->parish = 1;
			break;
		case "PARISH 2":
			$person->parish = 2;
			break;
		case "PARISH 3":
			$person->parish = 3;
			break;
		case "PARISH 4":
			$person->parish = 4;
			break;
		case "PARISH 5":
			$person->parish = 5;
			break;
		case "PARISH 6":
			$person->parish = 6;
			break;
		case "TIF":
			$person->parish = 10;
			break;
		case "":
			break;
		case "Transitional":
			$person->parish = 20;
			break;
		default:
			throw new Exception($r['IndParish']);
	}

	$person->commit();


//	var_dump($person);
//
//	die();
	

//	var_dump($address);

}

echo "Done Importing People";