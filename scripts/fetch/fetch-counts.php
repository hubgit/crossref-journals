<?php

define('INPUT_FILE', __DIR__ . '/../../data/original/crossref-titles.csv');
define('OUTPUT_FILE', __DIR__ . '/../../data/original/crossref-counts.csv');

$input = fopen(INPUT_FILE, 'r');
fgetcsv($input); // header row

$output = fopen(OUTPUT_FILE, 'w');
fputcsv($output, array('ISSN', 'ISSN2', 'DOIs (total)', 'DOIs (2012)', 'Journal', 'Publisher'));

while (($line = fgetcsv($input)) !== false) {
	list($title, $publisher, $subjects, $issns) = $line;

	$issns = array_filter(array_map('validate_issn', explode('|', $issns)));

	if (!$issns) {
		continue;
	}

	print_r($issns);

	$issn = $issns[0];

	$data = array(
		'issn' => $issn,
		'issn2' => $issns[1],
		'dois-total' => crossref_count($issn),
		'dois-2012' => crossref_count($issn, 2012),
		'title' => trim(str_replace('\\"', '', $title)),
		'publisher' => trim($publisher),
	);

	fputcsv($output, $data);
}

function validate_issn($issn) {
	if (preg_match('/^([0-9]{4})-?([0-9X]{4})$/', trim($issn), $matches)) {
		return $matches[1] . '-' . $matches[2];
	}
}

function crossref_count($issn, $year = null) {
	$params = array(
		'q' => $issn,
		'year' => $year,
		'type' => 'Journal Article',
		'header' => 'true',
		'rows' => 0,
	);

	$url = 'http://search.labs.crossref.org/dois?' . http_build_query($params);
	print "$url\n";

	$context = stream_context_create(array('http' => array('timeout' => 10)));
	$result = json_decode(file_get_contents($url, false, $context));
	//$result = json_decode(file_get_contents($url));

	if (!isset($result->totalResults)) {
		$message = sprintf('%s: No totalResults for %s (%d)', date(DATE_ATOM), $issn, $year);
		file_put_contents('counts.log', $message, FILE_APPEND);
	}

	return $result->totalResults;
}
