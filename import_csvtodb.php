<?php

if (file_exists('database.sqlite3')) {
	unlink('database.sqlite3');
}

global $pdo, $directory;

$pdo = new PDO('sqlite:database.sqlite3');

$directory = 'K:/SkyDrive/Tenth/Data/DbDump/';

foreach (new DirectoryIterator($directory) as $file) {
	if($file->isDot()) continue;
	import($file->getFilename());
}


// build the addresses table, for the address finding function to use.

die();  // The Address table should not be rebuilt unless absolutely imperative, because geocoding is expensive.

$pdo->exec('CREATE TABLE `_locations` (
	id		int PRIMARY KEY,
	address	varchar(255),
	state	varchar(2),
	name	varchar(255),
	type	int,
	lat		double,
	long	double,
	accur	varchar(12)
)');

echo "done";


function import($filename) {

	global $pdo, $directory;

	if (($handle = fopen($directory . $filename, "r")) !== FALSE) {
		$headers = fgetcsv($handle, ",");

		$filename = substr($filename, 0, -4);

		$pdo->exec('CREATE TABLE ' . $filename . '(`' . implode('`,`', $headers) . '`)');




$row = 1;

		$pdo->beginTransaction();
		while (($data = fgetcsv($handle, ",")) !== FALSE) {
			$row++;

			$stmt = 'INSERT INTO ' . $filename . ' (`' . implode('`, `', $headers) . '`) VALUES (\'' . implode('\', \'', $data) . "');\n";

//			echo $stmt;

			$pdo->exec($stmt);

			if ($row % 50 === 0) {
				set_time_limit(10);
				$pdo->commit();
				$pdo->beginTransaction();
			}

		}

		$pdo->commit();

		fclose($handle);
	}

}

