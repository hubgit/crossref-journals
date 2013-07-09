<?php

// edit these to suit the OAI service and local file system
define('OUTPUT_DIRECTORY', __DIR__ . '/../../data/original/');

// REST: collection URL
define('OAI_SERVER', 'http://oai.crossref.org/OAIHandler');

if (!file_exists(OUTPUT_DIRECTORY)) {
	mkdir(OUTPUT_DIRECTORY, 0777, true);
}

$output = fopen(OUTPUT_DIRECTORY . 'crossref-oai-sets.tsv', 'w');

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');

do {
	// REST: GET collection URL
	$params = array('verb' => 'ListSets');

	// REST: rel="next" URL for pagination, "Accept" header for format
	if ($resumptionToken) {
		$params['resumptionToken'] = $resumptionToken;
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

	$nodes = $xpath->query('oai:set', $root);
	foreach ($nodes as $node) {
		$data = array(
			'set' => $xpath->query('oai:setSpec', $node)->item(0)->textContent,
			'name' => $xpath->query('oai:setName', $node)->item(0)->textContent,
		);

		fputcsv($output, $data, "\t");
	}

	// REST: rel="next" Link header for pagination
	$nodes = $xpath->query('oai:resumptionToken', $root);
	$resumptionToken = $nodes->length ? $nodes->item(0)->textContent : null;
	print "\tresumptionToken: $resumptionToken\n";
} while ($resumptionToken);
