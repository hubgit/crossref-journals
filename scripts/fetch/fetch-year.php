<?php

$year = 2012;

define('INPUT_FILE', __DIR__ . '/../../data/original/crossref-titles.csv');

define('OUTPUT_DIRECTORY', __DIR__ . '/../../data/original/crossref-year/' . $year . '/');

if (!file_exists(OUTPUT_DIRECTORY)) {
	mkdir(OUTPUT_DIRECTORY, 0777, true);
}

$input = fopen(INPUT_FILE, 'r');

while (($line = fgetcsv($input)) !== false) {
	list($title, $publisher, $subjects, $issns) = $line;

	if (!$issns) {
		continue;
	}

	$issns = explode('|', $issns);

	if (!preg_match('/^([0-9]{4})-?([0-9X]{4})$/', $issns[0], $matches)) {
		print "Abnormal ISSN:\n";
		print_r($issns);
		continue;
	}

	$issn = $matches[1] . '-' . $matches[2];
	$output = fopen(OUTPUT_DIRECTORY . $issn . '.csv', 'w');

	$params = array(
		'q' => $issn,
		'year' => $year,
		'rows' => 100,
		'page' => 1,
	);

	//do {
		$url = 'http://search.labs.crossref.org/dois?' . http_build_query($params);
		print "$url\n";
		$items = json_decode(file_get_contents($url), true);

		foreach ($items as $item) {
			$data = array(
				'doi' => preg_replace('/^http:\/\/dx\.doi\.org\//', '', $item['doi']),
				'coins' => $item['coins'],
			);

			fputcsv($output, $data);
		}
	//} while ($items);
}
