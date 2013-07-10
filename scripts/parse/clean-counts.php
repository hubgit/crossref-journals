<?php

define('INPUT_FILE', __DIR__ . '/../../data/original/crossref-counts.csv');
define('OUTPUT_FILE', __DIR__ . '/../../data/parsed/crossref-counts-clean.csv');

$input = fopen(INPUT_FILE, 'r');
$fields = fgetcsv($input); // header row
print_r($fields);

$output = fopen(OUTPUT_FILE, 'w');
fputcsv($output, $fields);

$falseCounts = array();
$falseISSNs = array('0000-0000', '9999-9999');
foreach ($falseISSNs as $falseISSN) {
	$falseCounts[$falseISSN] = array(
		'DOIs (total)' => crossref_count($falseISSN),
		'DOIs (2012)' => crossref_count($falseISSN, 2012),
	);
}

print_r($falseCounts);

$seen = array();

while (($line = fgetcsv($input)) !== false) {
	$data = array_combine($fields, $line);

	$issns = explode('|', $data['ISSN']);
	$newIssns = array();

    // same DOI twice on the same row
	if (isset($issns[1])) {
		if ($issns[1] == $issns[0]) {
			$data['DOIs (total)'] = $data['DOIs (total)'] / 2;
			$data['DOIs (2012)'] = $data['DOIs (2012)'] / 2;
			unset($issns[1]);
		}
	}

	// false DOI
	foreach ($issns as $issn) {
		if (array_key_exists($issn, $falseCounts)) {
			print_r($data);
			$data['DOIs (total)'] -= $falseCounts[$issn]['DOIs (total)'];
			$data['DOIs (2012)'] -= $falseCounts[$issn]['DOIs (2012)'];
			print_r($data);
		} else {
			$newIssns[] = $issn;
			$seen[$issn][] = sprintf('%s (%d)', $data['Journal'], $data['DOIs (total)']);
		}
	}

	$data['ISSN'] = implode('|', $newIssns);

	fputcsv($output, $data);
}

$seen = array_filter($seen, function($titles) {
	return count($titles) > 1;
});

asort($seen);
print_r($seen);

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
