#!/bin/bash

DIR=../../data/original

curl -L http://www.crossref.org/titlelist/titleFile.csv -o $DIR/crossref-titles.csv
#curl -L https://github.com/hubgit/journal-names/raw/master/data/collapsed.csv -o $DIR/combined-titles.csv
