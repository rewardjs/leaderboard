<?php

// change this setting
$mysqli = new mysqli("localhost", "leaderboard", "", "leaderboard");

/* check connection */ 
if (mysqli_connect_errno()) {
   printf("Connect failed: %s\n", mysqli_connect_error());
   exit();
}

/* Database schema:

CREATE TABLE rewards_leaderboard (
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	ticket_id INT NOT NULL,
	author_github VARCHAR(20) NOT NULL,
	pull_request TEXT NOT NULL,
	closed_on DATE NOT NULL,
	INDEX author_github (author_github),
	INDEX closed_on (closed_on)
);

*/