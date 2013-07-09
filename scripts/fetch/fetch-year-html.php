<?php

$year = 2012;

define('INPUT_DIRECTORY', __DIR__ . '/../../data/original/crossref-year/' . $year . '/');
define('OUTPUT_DIRECTORY', __DIR__ . '/../../data/original/crossref-year-html/' . $year . '/');

if (!file_exists(OUTPUT_DIRECTORY)) {
	mkdir(OUTPUT_DIRECTORY, 0777, true);
}

$files = glob(INPUT_DIRECTORY . '*.csv');
$files = array_reverse($files);

$curl = curl_init();
curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_TIMEOUT, 10);
curl_setopt($curl, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
curl_setopt($curl, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:24.0) Gecko/20100101 Firefox/24.0'); // TODO: actual user agent

foreach ($files as $file) {
	$issn = basename($file, '.csv');

	$dir = OUTPUT_DIRECTORY . $issn;
	if (!file_exists($dir)) {
		mkdir($dir, 0777, true);
	}

	$dir = realpath($dir);
	print "$dir\n";

	$input = fopen($file, 'r');
	$count = 0;

	while (($line = fgetcsv($input)) !== false) {
		list($doi) = $line;

		// only fetch 5 articles for each ISSN
		if (++$count == 6) {
			break;
		}

		$report = $dir . '/' . base64_encode($doi) . '.json';
		$output = $dir . '/' . base64_encode($doi) . '.html.gz';

		if (file_exists($report)) {
			continue;
		}

		print "$doi\n";
		curl_setopt($curl, CURLOPT_URL, 'http://dx.doi.org/' . $doi);

		$result = curl_exec($curl);
		$info = curl_getinfo($curl);
		print_r($info);

		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
		print "$url\n";

		$data = array(
			'code' => $code,
			'issn' => $issn,
			'doi' => $doi,
			'url' => $url,
			'info' => $info,
		);

		file_put_contents($report, json_encode($data));

		if ($code == 200) {
			print "$output\n";
			file_put_contents('compress.zlib://' . $output, $result);
		} else {
			print "Error code $code\n";
			break; // skip to next ISSN
		}
	}
}
