<?php
/*
  QUICK BACKEND -- On the production server this should be secured via an .htaccess / htpasswd file
*/

// feel free to change these on live to create a simple username/pass auth
$username = ""; $password = "";

if ($username && !isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) && ($_SERVER['PHP_AUTH_USER'] != $username || $_SERVER['PHP_AUTH_PW'] != $password)) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'You need to login';
    exit;
}


require_once("mysql.inc.php");
require_once("trac.php");

if (isset($_POST['ticket'])) {
	$insert_query = $mysqli->prepare("INSERT INTO rewards_leaderboard ( ticket_id, author_github, pull_request, closed_on ) VALUES ( ?, ?, ?, ?)");
	$insert_query->bind_param("isss", $ticket, $git, $pull, $closed);
	foreach ($_POST['ticket'] as $id => $info) {
		if (!empty($info['elligable'])) {
			$ticket = $info['ticket_id'];
			$git = $info['author_github'];
			$pull = $info['pull_request'];
			$closed = $info['closed_on'];
		}
		$insert_query->execute();
		$insert_query->affected_rows or die("Couldn't insert");
	}
	$insert_query->close();
	header("Location: newticket.php"); exit;
}

function getTickets() {
	if (isset($_GET["from"])) {
		$from = $_GET["from"];
	} else {
		$from = false;
	}
	$trackerDomain = 'bugs.jqueryui.com';
	$startDate = '2011-05-02';
	$fixed = getFixedTickets( $trackerDomain, $from );
	$tickets = array();
	
	$rid = "#http://".$trackerDomain."/ticket/(\d+)#";
	$rpull = "/Merge pull request .+#(\d+)<\/a> from ([^\/]+)\//";
	
	foreach ($fixed as $xmlTicket) {
		if (preg_match($rid, $xmlTicket->link, $matches)) {
			$id = $matches[1];
		} else {
			continue; // couldn't parse ticket ID
		}
		// var_dump($xmlTicket);
		$closed = date("Y-m-d", strtotime($xmlTicket->pubDate));
		// skip tickets before contest start...
		if ($closed < $startDate) continue;
		$author = "";
		$pull = "";
		if (preg_match($rpull, $xmlTicket->description, $matches)) {
			$pull = "https://github.com/jquery/jquery-ui/pull/".$matches[1];
			$author = $matches[2];
		}
		
		$tickets[$id] = array( "ticket_id" => $id, "closed_on" => $closed, "pull_request" => $pull, "author_github" => $author, "description" => $xmlTicket->description);
	}
	return $tickets;
}

$tickets = getTickets();
$stmt = $mysqli->prepare("SELECT ticket_id, closed_on, author_github, pull_request FROM rewards_leaderboard WHERE ticket_id = ?");
$stmt->bind_result($ticket_id, $closed_on, $pull_request, $author_github);
echo "Check the box / Fill in the github/pull request for elligable tickets and submit form.";
echo "<form method='post'><table>";
echo "<tr><th>Ticket</th><th>Date</th><th>fixer's github</th><th>pull request</th></tr>";

foreach ($tickets as $ticket) {
	$stmt->bind_param( "i", $ticket['ticket_id'] );
	$stmt->execute() or die('Error executing query');
	if ( $result = $stmt->fetch() ) {
		echo "<tr class='in_database'><td><a href='http://bugs.jqueryui.com/ticket/".$ticket_id."'>$ticket_id</a></td>";
		echo "<td>$closed_on</td><td><a href='$pull_request'>$pull_request</a></td><td><a href='https://github.com/$author_github'>$author_github</a></td></tr>";
		continue;
	} else {
		extract($ticket);
		echo "<tr><td>";
		echo "<input type='checkbox' name='ticket[$ticket_id][elligable]' value='true' title='Insert into DB'>";
		echo "<input type='hidden' name='ticket[$ticket_id][ticket_id]' value='$ticket_id'>";
		echo "<a href='http://bugs.jqueryui.com/ticket/$ticket_id'>$ticket_id</a>";
		echo "</td>";
		echo "<td>";
		echo "<input type='hidden' name='ticket[$ticket_id][closed_on]' value='$closed_on'>";
		echo $closed_on;
		echo "</td>";
		echo "<td>";
		echo "<input type='text' name='ticket[$ticket_id][author_github]' size='12' value='$author_github'>";
		echo "</td>";
		echo "<td>";
		echo "<input type='text' name='ticket[$ticket_id][pull_request]' size='50' value='$pull_request'>";
		echo "</td>";
	}

}
echo "</table><input type='submit'></form>";
echo "<a href='?from=".urlencode(date("m/d/y", strtotime($closed_on)))."'>Further back</a>";
