#!/bin/bash
box build -v
box info output/reporter.phar

VERSION=`php output/reporter.phar --version`

SIG=`box info output/reporter.phar | grep Hash | cut -d ' ' -f 3`

echo -e "{\"version\":\"$VERSION\",\"signature\":\"$SIG\"}" > output/reporter.manifest
