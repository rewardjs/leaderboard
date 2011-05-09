<?php
require_once("mysql.inc.php");
# get requested report (defaults to all)
$date = false;
$date_start = "2011-05-02";
$date_end = "2011-05-28";
$report = "all";
if (!empty($_GET['report'])) {
	$report = $_GET['report'];
	$date = strtotime($report);
}
if ($date) {
	$date_start = date("Y-m-d",$date);
	$date_end = date("Y-m-d",$date);
}

$stmt = $mysqli->prepare("SELECT author_github as author, count(ticket_id) as count FROM rewards_leaderboard WHERE closed_on >= ? and closed_on <= ? GROUP BY author_github ORDER BY count");
$stmt->bind_param("ss", $date_start, $date_end);
$stmt->execute() or die("Couldn't get data");
$stmt->bind_result($author, $count);
$commits = array();
while($stmt->fetch()) {
	$commits[] = array('author'=>$author, 'count' => $count);
}

function count_cmp($a, $b)
{
	if ($a['count'] == $b['count']) {
		return 0;
	}
	return ($a['count'] < $b['count']) ? 1 : -1;
}

usort($commits, "count_cmp");

# return the proper output
header('Content-type: text/html');

# TODO: use a templating language (e.g. h2o) instead of 
#   hard-coding it in the php.

include('index.tmpl');
