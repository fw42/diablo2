<?
/////////////////////////////////////////////////
// Parsing the Ladder XML File                 //
//  Florian 'fw' Weingarten <flo@hackvalue.de> //
/////////////////////////////////////////////////

$file = "/home/diablo/var/ladders/d2ladder.xml";

require_once("/home/www/server/ladder/simplexml/IsterXmlSimpleXMLImpl.php");
$xml = new IsterXmlSimpleXMLImpl;

if(file_exists($file)) {
	$doc = $xml->load_file($file);
	
	print "\n+----+-------------+---------------+----+------------+------------+--------+\n";
	
	foreach($doc->D2_ladders->ladder as $ladder) {
		if($ladder->mode->CDATA() == "Hardcore") {
			if($ladder->class->CDATA() == "OverAll") {

				foreach($ladder->char as $char) {
					$rank = $char->rank->CDATA();
					$name = $char->name->CDATA();
					$level = $char->level->CDATA();
					$exp = $char->experience->CDATA();
					$class = $char->class->CDATA();
					$prefix = $char->prefix->CDATA();
					$status = $char->status->CDATA();

					if($status == "alive") {
						$status = "lebend";
					} else {
						$status = "tot";
					}

					printf("| %2d | %11s | %14s | %2d | %10d | %10s | %6s |\n", $rank, $class, $name, $level, $exp, $prefix, $status);;
				}
			}
		}
	}

	print "+----+-------------+---------------+----+------------+------------+--------+\n\n";

} else {
	exit("File '$file' not found.");
}


?>
