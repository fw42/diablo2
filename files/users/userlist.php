<?php

require "parsecharlist.php";

$userroot = "/home/diablo/var/users";
$statusfile = "/home/diablo/var/status/server.dat";

// those accounts will not be displayed!
$hideusers = array(".", "..", "public-gate", "public-mule", "admin");

$users = array();

if(is_dir($userroot)) {
	if($dh = opendir($userroot)) {
		while(($file = readdir($dh)) != false) {
			$skip = 0;
			foreach($hideusers as $user) {
				if($user == $file) {
					$skip = 1;
					break;
				}
			}

			if(!$skip) {
				array_push($users, $file);
			}
		}
	}
}

natcasesort($users);

$data = array();

foreach($users as $user) {
	$string = file_get_contents($userroot . "/" . $user);
	$data[$user] = array();
	$data[$user]['filename'] = $user;
	$data[$user]['filecontent'] = array();
	$data[$user]['filecontent'] = split("\n", $string);
	
	foreach($data[$user]['filecontent'] as $line) {
		list($foo, $bar) = explode("=", $line);
		$foo = str_replace("\"", "", $foo);
		$foo = str_replace('\\\\', "_", $foo);
		$bar = str_replace("\"", "", $bar);
		
		$data[$user][$foo] = $bar;
	}
}

function is_online($user) {
	$file_content = file_get_contents($statusfile);
	if (preg_match ("/" . $user . "/i", $file_content)) {
		return " (<font color=\"lime\">online</font>)";
	} else {
		return " (<font color=\"red\">offline</font>)";
	}
}

foreach($data as $user) {
	$stats = array(
		"Last Login"		=>	'BNET_acct_lastlogin_time',
		"Description"		=>	'profile_description',
		"Sex"			=>	'profile_sex',
		"Location"		=>	'profile_location'
	);

	echo "<table>\n";
	echo " <tr><td colspan=\"2\"><h2><a name=\"" . strtolower($user["filename"]) . "\">$user[filename]</a>".is_online($user['filename'])."</h2></td></tr>\n";

	foreach($stats as $key => $value) {
		$user[$value] = str_replace('\r\n', "<br>", $user[$value]);
		echo " <tr><td valign=\"top\"><u>$key:</u></td><td>";

		if($user[$value]) {
			if($key == "Last Login") {
				echo date("d.m.Y, G:i:s", $user[$value]);
			} else {
				echo $user[$value];
			}
		} else {
			echo "-";
		}
	
		echo "</td></tr>\n";
	}

	if($user['BNET_acct_ctime'] || $user['BNET_acct_firstlogin_time']) {
		if($user['BNET_acct_ctime']) {
			$time = $user['BNET_acct_ctime'];
		} elseif($user['BNET_acct_firstlogin_time']) {
			$time = $user['BNET_acct_firstlogin_time'];
		}
		
		echo " <tr><td><u>Created:</u></td><td>" . date("d.m.Y, G:i:s", $time) . "</td></tr>\n";
	}

	foreach($chars[$user['filename']] as $char) {
		$charlist .= "$char<br>\n";
	}

	echo " <tr><td valign=\"top\"><u>Characters:</u></td><td>$charlist</td></tr>\n";
	unset($charlist);

	echo "</table>\n<br>\n\n";
}

?>
