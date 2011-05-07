<?php

$templateUrl = "http://{{server}}/timeline?daysback=0&ticket=on&format=rss";


function count_cmp($a, $b)
{
    if ($a['count'] == $b['count']) {
        return 0;
    }
    return ($a['count'] < $b['count']) ? 1 : -1;
}

function getCommitsForToday($serverName) {
    global $templateUrl;
    if ($serverName) {
        $fetchUrl = str_replace("{{server}}", $serverName, $templateUrl);
        
        // init cURL
        $session = curl_init();

        // set url
        curl_setopt($session, CURLOPT_URL, $fetchUrl);

        //return the transfer as a string
        curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $output = curl_exec($session);

        // free up cURL resources
        curl_close($session);

        // process content
        $cleaned = str_replace("dc:creator", "creator", $output);
        $xml = new SimpleXMLElement($cleaned);
        
        $committers = Array();
        foreach($xml->channel[0]->item as $item) {
            if ($item->category == "closedticket") {
                if (substr($item->description, 0, 5) == "fixed") {
                    $creator = (string)$item->creator;
                    if (array_key_exists($creator, $committers)) {
                        $count = (integer)$committers[$creator]['count'];
                        $committers[$creator]['count'] = $count + 1;
                    } else {
                        $committers[$creator] = Array("creator" => $creator, "count" => 1);
                    }
                    //array_push($commits, $item);
                }
            }
        }
        
        usort($committers, "count_cmp");
    }
    return $committers;
}

?>