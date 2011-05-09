<?php

$templateUrl = "http://{{server}}/timeline?daysback=0{{days}}&ticket=on&format=rss";


function count_cmp($a, $b)
{
    if ($a['count'] == $b['count']) {
        return 0;
    }
    return ($a['count'] < $b['count']) ? 1 : -1;
}

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

function getCommitsForToday($serverName) {
    global $templateUrl;
    if ($serverName) {
        $fetchUrl = str_replace("{{server}}", $serverName, $templateUrl);
        $fetchUrl = str_replace("{{days}}", "0", $fetchUrl);
        
        // fetch raw RSS from Trac
        $rawRSS = fetch($fetchUrl);

        // process content
        $committers = getFixedFromRss($rawRSS);
        
        // sort results by bug count, descending
        usort($committers, "count_cmp");
    }
    return $committers;
}

function getCommitsForThisWeek($serverName) {
    global $templateUrl;
    $dayOfTheWeek = date("l");
    if ($serverName) {
        $fetchUrl = str_replace("{{server}}", $serverName, $templateUrl);
        $fetchUrl = str_replace("{{days}}", $dayOfTheWeek, $fetchUrl);
        
        // fetch raw RSS from Trac
        $rawRSS = fetch($fetchUrl);

        // process content
        $committers = getFixedFromRss($rawRSS);
        
        // sort results by bug count, descending
        usort($committers, "count_cmp");
    }
    return $committers;
}

?>