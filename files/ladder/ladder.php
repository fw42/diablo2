<?
/////////////////////////////////////////////////
// Parsing the Ladder XML File                 //
//  Florian 'fw' Weingarten <flo@hackvalue.de> //
/////////////////////////////////////////////////

$xmlfile = "/home/diablo/var/ladders/d2ladder.xml";
$accountsdir = "/home/diablo/var/charinfo";

/////////////////////////////////////////////////

require_once("simplexml/IsterXmlSimpleXMLImpl.php");
$xml = new IsterXmlSimpleXMLImpl;

$handle = opendir($accountsdir);

$accounts = array();
while(($file = readdir($handle)) != false) {
	if($file != "." && $file != "..") {
		$handlezwei = opendir("$accountsdir/$file");

		while(($filezwei = readdir($handlezwei)) != false) {
			if($filezwei != "." && $filezwei != "..") {
				$accounts["$filezwei"] = "$file";
			}
		}
	}
}

$anzahl_tote = $anzahl_lebende = 0;

if(file_exists($xmlfile)) {
	$doc = $xml->load_file($xmlfile);
	
	print "<table cellpadding=\"2\" cellspacing=\"2\">";
	foreach($doc->D2_ladders->ladder as $ladder) {
		if($ladder->mode->CDATA() == "Hardcore") { // only Hardcore
			if($ladder->class->CDATA() == "OverAll") { // only OverAll
				print " <tr><colspan=\"7\">Hardcore Ladder</th></tr>\n";
				print " <tr><th>#</th><th>Name</th><th>Level</th><th>Exp</th><th>Class</th><th>Title</th><th>Status</th></tr>\n";

				foreach($ladder->char as $char) {
					$rank = $char->rank->CDATA();
					$name = $char->name->CDATA();
					$level = $char->level->CDATA();
					$exp = $char->experience->CDATA();
					$class = $char->class->CDATA();
					$prefix = $char->prefix->CDATA();
					$status = $char->status->CDATA();
					$acc = $accounts[strtolower($name)];
					
					if($status == "alive") {
						$status = "<font color=\"green\">alive</font>";
						$anzahl_lebende++;
					} else {
						$status = "<font color=\"red\">dead</font>";
						$anzahl_tote++;
					}

					print " <tr><td>$rank</td><td><img src=\"/server/ladder/icons/$class.gif\" alt=\"$class\">";
					if ($acc) {
						print "$name (<small><a href=\"/user/#$acc\">*$acc</a></small>)";
					} else {
						print "<s>$name</s> (<small>deleted</small>)";
					}
					print "</td><td>$level</td><td>$exp</td><td>$class</td><td>$prefix</td><td>$status</td></tr>\n";
				}
			}
		}
	}
	print "</table>\n";

} else {
	exit("File '$xmlfile' not found.");
}

print "<p>Total of <b>" . ($anzahl_tote + $anzahl_lebende) . "</b> characters in the ladder, <b>$anzahl_lebende</b> alive and <b>$anzahl_tote</b> dead.</p>";

?>
