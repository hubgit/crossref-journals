datadir = data/original

all: oai year html

$(datadir)/crossref-titles.csv:
	curl -L http://www.crossref.org/titlelist/titleFile.csv -o $(datadir)/crossref-titles.csv

$(datadir)/combined-titles.csv:
	curl -L https://github.com/hubgit/journal-names/raw/master/data/collapsed.csv -o $(datadir)/combined-titles.csv

$(datadir)/crossref-oai-sets.csv: $(datadir)/crossref-titles.csv
	php scripts/fetch/fetch-sets.php

$(datadir)/crossref-counts.csv: $(datadir)/crossref-titles.csv
	php scripts/fetch/fetch-year.php

oai: $(datadir)/crossref-oai-sets.csv
	php scripts/fetch/fetch-all-oai.php

year: $(datadir)/crossref-titles.csv
	php scripts/fetch/fetch-year.php
	php scripts/fetch/fetch-year-html.php