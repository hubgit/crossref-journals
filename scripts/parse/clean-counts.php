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

	if (!$data['ISSN']) {
		continue;
	}

	if (array_key_exists($data['ISSN'], $seen)) {
		print 'Seen ' . $data['ISSN'] . ' already';

		$previous = $seen[$data['ISSN']];

		if ($previous && $data['ISSN2'] && $previous != $data['ISSN2']) {
			print ' with ' . $previous;
		}

		print "\n";
	}

	$seen[$data['ISSN']] = $data['ISSN2'];

	if ($data['ISSN2']) {
		if (array_key_exists($data['ISSN2'], $seen)) {
			print 'Seen ' . $data['ISSN2'] . ' already';

			$previous = $seen[$data['ISSN2']];

			if ($previous && $data['ISSN'] && $previous != $data['ISSN']) {
				print ' with ' . $previous;
			}

			print "\n";
		}

		$seen[$data['ISSN2']] = $data['ISSN'];
	}

	fputcsv($output, $data);
}
