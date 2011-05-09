<?php

$templateUrl = "http://{{server}}/timeline?from={{from}}&daysback=7&ticket=on&format=rss";

function fetch($url) {
	// init cURL
	$session = curl_init();

	// set url
	curl_setopt($session, CURLOPT_URL, $url);

	//return the transfer as a string
	curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);

	// $body contains the output string
	$body = curl_exec($session);

	// free up cURL resources
	curl_close($session);

	return $body;
}

function filterFixed( $rss ) {
	$cleaned = str_replace("dc:creator", "creator", $rss);
	$xml = new SimpleXMLElement($cleaned);
	$fixed = array();

	foreach($xml->channel[0]->item as $item) {
		if ($item->category == "closedticket") {
			if (substr($item->description, 0, 5) == "fixed") {
				$fixed[] = $item;
			}
		}
	}
	return $fixed;

}

function getFixedFromRss($rss) {
	$cleaned = str_replace("dc:creator", "creator", $rss);
	$xml = new SimpleXMLElement($cleaned);

	$fixed = Array();
	foreach($xml->channel[0]->item as $item) {
		// we only want to count those entries that are both closed and fixed
		//   (i.e. not 'worksforme' or 'invalid')
		if ($item->category == "closedticket") {
			if (substr($item->description, 0, 5) == "fixed") {
				$creator = (string)$item->creator;
				if (array_key_exists($creator, $fixed)) {
					$count = (integer)$fixed[$creator]['count'];
					$fixed[$creator]['count'] = $count + 1;
				} else {
					$fixed[$creator] = Array("creator" => $creator, "count" => 1);
				}
			}
		}
	}
	return $fixed;
}

function getFixedTickets( $serverName, $from = false )
{
	global $templateUrl;
	if ( $from === false ) {
		$from = date("m/d/y");
	}
	if ($serverName) {
		$fetchUrl = str_replace(array(
			"{{server}}", "{{from}}"
		), array(
			$serverName, urlencode($from)
		), $templateUrl);
		// fetch raw RSS from Trac
		$rawRSS = fetch($fetchUrl);

		// process content
		$fixed = filterFixed( $rawRSS );
		return $fixed;
	}
}
?>