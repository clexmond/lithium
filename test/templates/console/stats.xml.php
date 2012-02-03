<?php

$testsuites = array();

foreach ($count as $type => $num) {
	if ($num > 0 and is_array($stats[$type])) {
		foreach ($stats[$type] as $result) {
			if ($type === 'skips') {
				$class = $result['trace'][0]['class'];
				$method = $result['trace'][1]['function'];
			} else {
				$class  = $result['class'];
				$method = $result['method'];
			}

			if (isset($testsuites[$class]) === false) {
				$testsuites[$class]['name'] = $class;
				$testsuites[$class]['assertions'] = 0;
				$testsuites[$class]['failures'] = 0; // fails
				$testsuites[$class]['errors'] = 0; // exceptions
			}

			if (isset($testsuites[$class]['cases'][$method]) === false) {
				$testsuites[$class]['cases'][$method]['assertions'] = 0;
				$testsuites[$class]['cases'][$method]['class'] = $class;
				$testsuites[$class]['cases'][$method]['line'] = $result['line'];
			}

			$result['type'] = $type;
			$testsuites[$class]['cases'][$method]['results'][] = $result;
		}
	}
}

$report = new \DOMDocument();
$report->formatOutput = true;
$testsuitesXML = $report->createElement('testsuites');

foreach ($testsuites as $testsuite) {
	$suiteTests = 0;
	$suiteAssertions = 0;
	$suiteSkips = 0;
	$suiteFailures = 0;
	$suiteErrors = 0;
	$testsuiteXML = $report->createElement('testsuite');
	$testsuiteXML->setAttribute('name', $testsuite['name']);

	foreach ($testsuite['cases'] as $method => $case) {
		$suiteTests++;
		$caseAssertions = 0;
		$errorLines = array();
		$testcaseXML = $report->createElement('testcase');
		$testcaseXML->setAttribute('name', $method);
		$testcaseXML->setAttribute('class', $testsuite['name']);

		foreach ($case['results'] as $result) {
			switch ($result['type']) {
				case 'passes':
					$caseAssertions++;
					break;

				case 'errors':
					if (in_array($result['line'], $errorLines) === false) {
						$caseAssertions++;
						$errorLines[] = $result['line'];
					}
					if ($result['result'] === 'exception') {
						$suiteErrors++;
						$error = $report->createElement('error');
						$error->setAttribute('type', 'Exception');
						$error->nodeValue = $result['trace'];
						$testcaseXML->appendChild($error);
					}
					if ($result['result'] === 'fail') {
						$suiteFailures++;
						$error = $report->createElement('failure');
						$error->setAttribute('type', $result['assertion']);
						$error->nodeValue = $result['message'] . "\n" . $result['file'] . ':' . $result['line'];
						$testcaseXML->appendChild($error);
					}
					break;

				case 'skips':
					$suiteSkips++;
					$skip = $report->createElement('skipped');
					$skip->setAttribute('type', 'skip');
					$skip->setAttribute('message', $result['message']);
					$skip->nodeValue = $result['message'] . "\n" . $result['trace'][0]['file'] . ':' . $result['line'];
					$testcaseXML->appendChild($skip);
					break;

				default:
					break;
			}
		}

		$suiteAssertions += $caseAssertions;
		$testcaseXML->setAttribute('assertions', $caseAssertions);
		$testsuiteXML->appendChild($testcaseXML);
	}

	$testsuiteXML->setAttribute('tests', $suiteTests);
	$testsuiteXML->setAttribute('assertions', $suiteAssertions);
	$testsuiteXML->setAttribute('failures', $suiteFailures);
	$testsuiteXML->setAttribute('errors', $suiteErrors);
	$testsuiteXML->setAttribute('skips', $suiteSkips);
	$testsuitesXML->appendChild($testsuiteXML);
}

$report->appendChild($testsuitesXML);
echo $report->saveXML();

?>