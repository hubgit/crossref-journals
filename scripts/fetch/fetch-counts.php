<?php

define('INPUT_FILE', __DIR__ . '/../../data/original/crossref-titles.csv');
define('OUTPUT_FILE', __DIR__ . '/../../data/original/crossref-counts.csv');

$input = fopen(INPUT_FILE, 'r');
$output = fopen(OUTPUT_FILE, 'w');

$context = stream_context_create(array('http' => array('timeout' => 10)));

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

	$params = array(
		'q' => $issn,
		'header' => 'true',
		'rows' => 0,
	);

	$url = 'http://search.labs.crossref.org/dois?' . http_build_query($params);
	print "$url\n";

	$result = json_decode(file_get_contents($url, false, $context), true);

	$data = array(
		'issn' => $issn,
		'count' => $result['totalResults'],
		'2012' => null,
		'publisher' => $publisher,
		'title' => $title,
	);

	$params = array(
		'q' => $issn,
		'year' => 2012,
		'header' => 'true',
		'rows' => 0,
	);

	$url = 'http://search.labs.crossref.org/dois?' . http_build_query($params);
	print "$url\n";

	$result = json_decode(file_get_contents($url, false, $context), true);

	$data['2012'] = $result['totalResults'];

	fputcsv($output, $data);
}
