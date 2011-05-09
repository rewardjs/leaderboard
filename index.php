<?php

# TODO: get config info on how to retrieve the latest commits
#  (for now, we'll hard-code this, but long-term, that won't
#   work if other projects beyond jQuery UI get involved)

$trackerType = 'trac';
$trackerDomain = 'bugs.jqueryui.com';

# load module to fetch the commit info
if ($trackerType == 'trac') {
    include('trac.php');
}

# get requested report (defaults to today)
$report = $_GET['report'];
if ($report == 'week') {
    $commits = getCommitsForThisWeek($trackerDomain);
} else {
    $report = 'today';
    $commits = getCommitsForToday($trackerDomain);
}

# return the proper output
header('Content-type: text/html');

# TODO: use a templating language (e.g. h2o) instead of 
#   hard-coding it in the php.

include('index.tmpl');
