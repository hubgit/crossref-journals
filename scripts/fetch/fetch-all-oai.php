<?php

define('INPUT_FILE', __DIR__ . '/../../data/original/crossref-oai-sets.tsv');

// edit these to suit the OAI service and local file system
define('OUTPUT_DIRECTORY', __DIR__ . '/../../data/original/crossref-oai/');

// REST: collection URL
define('OAI_SERVER', 'http://oai.crossref.org/OAIHandler');

// REST: "Accept" header
define('METADATA_PREFIX', 'cr_unixml');

if (!file_exists(OUTPUT_DIRECTORY)) {
	mkdir(OUTPUT_DIRECTORY, 0777, true);
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
curl_setopt($curl, CURLOPT_TIMEOUT, 60);

$input = fopen(INPUT_FILE, 'r');

while (($line = fgetcsv($input, null, "\t")) !== false) {
	print_r($line);
	list($set, $setName) = $line;

	$file = OUTPUT_DIRECTORY . preg_replace('/\W/', '-', $set) . '.csv';

	if (file_exists($file)) {
		continue;
	}

	$resumptionToken = null;
	$output = fopen($file, 'w');

	do {
		// REST: GET collection URL
		$params = array('verb' => 'ListIdentifiers');

		// REST: rel="next" URL for pagination, "Accept" header for format
		if ($resumptionToken) {
			$params['resumptionToken'] = $resumptionToken;
		} else {
			$params['set'] = $set;
			$params['metadataPrefix'] = METADATA_PREFIX;
		}

		// REST: collection URL
		$url = OAI_SERVER . '?' . http_build_query($params);
		curl_setopt($curl, CURLOPT_URL, $url);

		print "Fetching $url\n";
		$result = curl_exec($curl);

		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if (200 !== $code) {
			print "\tUnexpected code $code for $url\n";
		}

		$dom = new DOMDocument;
		$dom->loadXML($result);

		$xpath = new DOMXPath($dom);
		$xpath->registerNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');

		$root = $xpath->query('oai:' . $params['verb'])->item(0);

		$nodes = $xpath->query('oai:header', $root);
		foreach ($nodes as $node) {
			$data = array(
				'set' => $set,
				'id' => $xpath->query('oai:identifier', $node)->item(0)->textContent,
				'date' => null,
			);

			$dates = $xpath->query('oai:datestamp', $node);

			if ($dates->length) {
				$data['date'] = $dates->item(0)->textContent;
			}

			fputcsv($output, $data);
		}

		// REST: rel="next" Link header for pagination
		$nodes = $xpath->query('oai:resumptionToken', $root);
		$resumptionToken = $nodes->length ? $nodes->item(0)->textContent : null;
		print "\tresumptionToken: $resumptionToken\n";
	} while ($resumptionToken);
}
