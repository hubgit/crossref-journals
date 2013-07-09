<?php

$year = 2012;

define('INPUT_DIRECTORY', __DIR__ . '/../../data/original/crossref-year-html/' . $year . '/');
define('OUTPUT_FILE', __DIR__ . '/../../data/original/parsed-articles.csv');

$output = fopen(OUTPUT_FILE, 'w');

$dirs = glob(INPUT_DIRECTORY . '*', GLOB_ONLYDIR);

$fields = array();
$total = 0;
$issns = 0;

foreach ($dirs as $dir) {
	$issn = basename($dir);
	$files = glob($dir . '/*.html.gz');

	if (!$files) {
		print "No files for $issn\n";
		continue;
	}

	$issns++;

	$seenfields = array();
	$count = 0;
	foreach ($files as $file) {
		// only use 5 files from each ISSN
		if (++$count == 6) {
			continue;
		}

		$total++;

		$file = realpath($file);
		$doi = base64_decode(basename($file, '.html.gz'));

		$reportfile = preg_replace('/\.html\.gz$/', '.json', $file);
		$report = json_decode(file_get_contents($reportfile), true);

		// load the HTML file
		$html = file_get_contents('compress.zlib://' . $file);

		// if the server specified a content-type, add a meta tag to force the charset
		if ($contentType = $report['info']['content_type']) {
			// if this is a PDF, ignore it
			if (strpos($contentType, 'pdf') !== false) {
				continue;
			}

			// TODO: if there's no <head>, try <html> or before first < ?
			$replacement = sprintf('<head><meta http-equiv="Content-Type" content="%s">', htmlspecialchars($contentType));
			$html = preg_replace('/<head>/i', $replacement, $html);
		}

		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		@$dom->loadHTML($html);
		$dom->formatOutput = true;
		$dom->documentURI = $report['url'];

		$xpath = new DOMXpath($dom);
		$nodes = $xpath->query('//meta');

		//printf("%s meta nodes in %s\n", $nodes->length, $file);

		if (!$nodes->length) {
			continue;
		}

		$data = array(
			'doi' => null,
			'citation_id' => null,
			'citation_pmid' => null,
			'citation_title' => null,
			'citation_journal_title' => null,
			'citation_journal_abbrev' => array(),
			'citation_journal_title_abbrev' => null,
			'citation_language' => null,
			'citation_issn' => array(),
			'citation_isbn' => array(),
			'citation_volume' => null,
			'citation_issue' => null,
			'citation_firstpage' => null,
			'citation_lastpage' => null,
			'citation_date' => null,
			'citation_year' => null,
			'citation_collection_id' => array(),
			'citation_online_date' => null,
			'citation_publication_date' => null,
			'citation_publisher' => null,
			'citation_doi' => null,
			'citation_abstract' => array(),
			'citation_abstract_html_url' => null,
			'citation_fulltext_html_url' => null,
			'citation_pdf_url' => array(),
			'citation_author' => array(),
			'citation_author_email' => array(),
			'citation_author_institution' => array(),
			'citation_authors' => array(),
			'citation_keywords' => array(),
			'citation_reference' => false, //array(),
			'citation_public_url' => null,
			'citation_section' => null,
			'citation_mjid' => null,
			'citation_price' => null,
			'citation_bibcode' => null,
			'citation_access' => null,
			'citation_fulltext_world_readable' => null,
			'article_references' => false, //array(),
			'dc.date' => null,
			'dc.description' => false, //array(),
			'dc.format' => array(),
			'dc.identifier' => array(),
			'dc.language' => null,
			'dc.publisher' => null,
			'dc.rights' => null,
			'dc.source' => null,
			'dc.subject' => array(),
			'dc.title' => null,
			'dc.type' => array(),
			'dc.coverage' => null,
			'dc.creator' => array(),
			'dc.contributor' => array(),
			'dcterms.issued' => null,
			'dcterms.ispartof' => null,
			'dcterms.bibliographiccitation' => null,
			'fulltext_pdf' => array(),
			'robots' => array(),
			'robot' => false,
			'description' => array(),
			'author' => array(),
			'category' => null,
			'viewport' => false,
			'format-detection' => false,
			'keywords' => array(),
			'date' => null,
			'url' => null,
			'copyright' => null,
			'masterurl' => false,
			'subject' => null,
			'article-type' => null,
			'access-type' => null,
			'revisit-after' => false,
			'site development' => false,
			'mssmarttagspreventparsing' => false,
			'date-creation-yyyymmdd' => false,
			'apple-mobile-web-app-capable' => false,
			'google-translate-customization' => false,
			'ic.identifier' => false,
			'generator' => false,
			'icbm' => false,
			'fragment' => false,
			'objectid' => null,
			'rights' => null,
			'meta' => null,
			'title' => null,
			'documenttype' => false,
			'epubsubtype' => false,
			'documentsubtype' => false,
			'goid' => false,
			'pages' => null,
			'timecreated' => null,
			'timemodified' => null,
			'type' => null,
			'doi_suffix' => null,
			'doi_suffix1' => null,
			'session-search-history-count' => false,
			'session-marked-citation-count' => false,
			'session-marked-citations' => false,
			'googlebot' => false,
			'msnbot' => false,
			'slurp' => false,
			'arttype' => false,
			'google-site-verification' => false,
			'reply-to' => false,
			'design' => false,
			'publisher' => false,
			'rating' => false,
			'classification' => false,
			'msvalidate.01' => false,
			'hw.ad-path' => false,
			'hw.identifier' => false,
			'gs_meta_revision' => false,
			'dc.type.articletype' => null,
			'dc.source.volume' => null,
			'dc.source.uri' => null,
			'dc.source.issue' => null,
			'dc.source.issn' => null,
			'dc.identifier.uri' => null,
			'dc.identifier.doi' => null,
			'dc.identifier.pagenumber' => null,
			'dc.date.available' => null,
			'dc.date.modified' => null,
			'dc.date.issued' => null,
			'dc.date.datesubmitted' => null,
			'dc.date.created' => null,
			'dc.creator.personalname' => array(),
			'dc.date.x-metadatalastmodified' => null,
			'dc.contributor.sponsor' => array(),
			'dc.contributor.edt' => null,
			'dc.contributor.trl' => null,
			'dc.contributor.personalname' => null,
			'dc.title.alternative' => null,
			'dc.description.tableofcontents' => null,
			'dc.relation.ispartof' => null,
			'dc.relation' => array(),
			'dc.issued' => null,
			'dc.keyword' => array(),
			'dc.coverage' => array(),
			'dc.coverage.spatial' => array(),
			'dc.coverage.temporal' => array(),
			'dc.issued' => null,
			'dc.publisher.address' => null,
			'prism.issn' => null,
			'prism.url' => null,
			'prism.publicationname' => null,
			'prism.number' => null,
			'prism.volume' => null,
			'prism.issuename' => null,
			'prism.startingpage' => null,
			'prism.endingpage' => null,
			'prism.publicationdate' => null,
			'prism.coverdisplaydate' => null,
			'prism.issn' => null,
			'prism.eissn' => null,
			'prism.copyright' => null,
			'prism.doi' => null,
			'prism.section' => null,
			'prism.keyword' => array(),
			'prism.elssn' => null,
			'prism.teaser' => null,
			'prism.issueidentifier' => null,
			'dcs.dcs_uri' => false,
			'dcs.dcssip' => false,
			'dcs.dcsuri' => false,
			'dcsext.domain' => false,
			'dcsext.language' => false,
			'dcsext.subsubgroup' => false,
			'dcsext.doc_length' => false,
			'dcsext.inst_nom' => false,
			'dcsext.ct_pack' => false,
			'dcsext.dcssip' => false,
			'dcsext.pg_type' => false,
			'dcsext.pn_type' => false,
			'dcsext.ct_disc1' => false,
			'dcsext.ct_disc2' => false,
			'dcsext.pn_grid' => false,
			'dcsext.doc_format' => false,
			'dcsext.doc_type' => false,
			'dcsext.doc_plan' => false,
			'dcsext.pn-cat' => false,
			'dcsext.w_ci_id' => false,
			'dcsext.doc_nb_pages' => false,
			'dcsext.authors' => false,
			'dcsext.articlekeywords' => false,
			'dcsext.doc_shakespeare_abstract' => false,
			'dcsext.doc_shakespeare_fulltext' => false,
			'dscext.pn_grid' => false,
			'dcsext.doc_achatperenne' => false,
			'wt.pn_sku' => false,
			'wt.pn_id' => false,
			'wt.tx_e' => false,
			'wt.ti' => false,
			'wt.cg_n' => false,
			'wt.cg_s' => false,
			'ppl.doctype' => null,
			'ppl.volume' => null,
			'ppl.issue' => null,
			'ppl.firstpage' => null,
			'ppl.lastpage' => null,
			'ppl.doi' => null,
			'vaw_isbn13' => null,
			'vaw_issn' => null,
			'vaw_issn_online' => null,
			'vaw_image' => null,
			'vaw_prospektname' => null,
			'vaw_sachgebiet' => null,
			'vaw_media' => array(),
			'vaw_publication_type' => null,
			'vaw_publisher' => null,
			'vaw_quality_review' => null,
			'vaw_unit' => null,
			'vaw_volume' => null,
			'vaw_isbn13_online' => null,
			'identifier-url' => null,
			'language' => null,
			'hw_childaccess' => false,
			'hw_compoundsearchable' => false,
			'hw_effectiveaccess' => false,
			'hw_hidefromsearch' => false,
			'hw_objectname' => false,
			'name' => array(),
			'sortorder' => false,
			'subdocs' => false,
			'organization' => false,
			'revisit' => false,
			'distribution' => false,
			'pname' => false,
			'mainclass' => false,
			'ms.locale' => false,
		);

		foreach ($nodes as $node) {
			$name = trim(strtolower($node->getAttribute('name')));

			if (!$seenfields[$name]) {
				$fields[$name]++;
				$seenfields[$name] = 1;
			}

			if (!$name) {
				continue;
			}

			$content = trim($node->getAttribute('content'));
			$existing = $data[$name];

			if (!array_key_exists($name, $data)) {
				print "Unknown field $name in $file\n";
			} else if (is_array($existing)) {
				$data[$name][] = $content;
			} else if (is_string($existing)) {
				print "$name is already set in $file\n";
			} else if (is_null($existing)) {
				$data[$name] = $content;
			} else if ($existing === false) {
				// skip
			}
		}

		fputcsv($output, array($issn, $doi, json_encode(array_filter($data))));
	}
}

ksort($fields);
print_r($fields);
print "$issns ISSNs\n";
print "$total files\n";
