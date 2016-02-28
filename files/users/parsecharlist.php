<?
	$file = "/tmp/charlist.txt";

	$handle = fopen($file, "r");
	$chars = array();
	$done = array();

	while(!feof($handle)) {
		$line = fgets($handle);
		if($line == "") { continue; }
		list($acc, $char, $level, $class, $dead) = split(" ", $line);
		
		if($done[$acc] != 1) {
			$chars[$acc] = array();
			$done[$acc] = 1;
		}
		
		if($dead == "tot\n") { $dead = ", <font color=\"red\">dead</font>"; } else { $dead = ", <font color=\"lime\">alive</font>"; };
		$outp = "$char (<small>Level $level $class" . $dead . "</small>)";
		array_push($chars[$acc], $outp);

	}
?>	
