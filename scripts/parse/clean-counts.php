<?php

define('INPUT_FILE', __DIR__ . '/../../data/original/crossref-counts.csv');
define('OUTPUT_FILE', __DIR__ . '/../../data/parsed/crossref-counts-clean.csv');

$input = fopen(INPUT_FILE, 'r');
$fields = fgetcsv($input); // header row
print_r($fields);

$output = fopen(OUTPUT_FILE, 'w');
fputcsv($output, $fields);

$fake = array(
	'0000-0000' => true,
	'1234-5678' => true,
	'7777-8888' => true,
	'9999-9999' => true,
);

$seen = array();

while (($line = fgetcsv($input)) !== false) {
	$data = array_combine($fields, $line);

	// remove false DOIs
	foreach (array('ISSN', 'ISSN2') as $field) {
		$issn = $data[$field];

		if (array_key_exists($issn, $fake)) {
			unset($data[$field]);
		}
	}

	$issn = $data['ISSN'];

	if (!$issn) {
		continue;
	}

	if (array_key_exists($issn, $seen)) {
		if ($data['Journal'] && $data['Journal'] != $seen[$issn]['Journal']) {
			$seen[$issn]['Journal'] .= "\n" . $data['Journal'];
		}

		if ($data['Publisher'] && $data['Publisher'] != $seen[$issn]['Publisher']) {
			$seen[$issn]['Publisher'] .= "\n" . $data['Publisher'];
		}

		if ($data['ISSN2']) {
			$seen[$issn]['ISSN2'] .= "\n" . $data['ISSN2'];
		}

		if ($data['DOIs (total)'] > $seen[$issn]['DOIs (total)']) {
			$seen[$issn]['DOIs (total)'] = $data['DOIs (total)'];
		}

		if ($data['DOIs (2012)'] > $seen[$issn]['DOIs (2012)']) {
			$seen[$issn]['DOIs (2012)'] = $data['DOIs (2012)'];
		}
	} else {
		$seen[$issn] = $data;
	}
}

foreach ($seen as $issn => $data) {
	fputcsv($output, $data);
}
