<?php

if(isset($_GET['catch']))
{
        require("lib/sprites.php");
	$monID = (int)$_GET['catch'];
	$userID = (int)$_COOKIE['user'];
  $spritehash = $_GET['t'];
	if($userID == 0) die("Not logged in.");
	require("lib/config.php");
  if($spritehash != generate_sprite_hash($userID,$monID)) die("Not a valid 
capture.");
	mysql_connect($sqlhost,$sqluser,$sqlpass) or die("Couldn't connect to MySQL server.");
	mysql_select_db($sqldb) or die("Couldn't find MySQL database.");

 

	mysql_query("INSERT IGNORE INTO sprite_captures VALUES(".$userID.", ".$monID.")") or die("Could not register capture.");
	if(mysql_affected_rows() == 1)
	{
		$monName = mysql_result(mysql_query("SELECT name FROM sprites WHERE id=".$monID), 0, 0);
		$grats = "Congratulations. You caught ".$monName;
		
		//Granting a badge for catching N sprites
		$numCaught = mysql_result(mysql_query("SELECT COUNT(*) FROM sprite_captures WHERE userid=".$userID), 0, 0);
		if($numCaught == 7)
		{
			//mysql_query("INSERT IGNORE INTO usertokens VALUES(".$userID.", 100)");
			//$grats .= " and got the Dodongo Badge!";
			$grats .= "!";
		}
		else
			$grats .= "!";
		
		die($grats);
	}
	die("OK");
}

require("lib/common.php");
pageheader();
if(!$log)
{
	print "
	$L[TBL1]>
		$L[TD1c]>
			You must be logged in to check your captured Sprites!<br>
			<a href=./>Back to main</a> or <a href=login.php>login</a>
	$L[TBLend]
";
	pagefooter();
	die();
}

$captureReq = $sql->query("SELECT monid FROM sprite_captures WHERE userid = ".$loguser['id']);
$captures = array();
while($capt = $sql->fetch($captureReq))
	$captures[$capt['monid']] = true;

$headers = array
(
	"id" => array //Entry key is used in $data to bind fields
	(
		"caption" => "#",
		"width" => "32px",
		"align" => "center",
		"color" => 1
	),
	"img" => array("caption"=>"Image", "width"=>"32px", "color"=>2),
	"name" => array("caption"=>"Name", "align"=>"center", "color"=>1),
	"flavor" => array("caption"=>"Description", "color"=>2),

	//Hidden flag could be used for admin-only columns.
	"secretbuttfun" => array("caption"=>"You can't see this one!", "hidden"=>true),
);

$data = array();
$monReq = $sql->query("SELECT * FROM sprites ORDER BY id ASC");
while($mon = $sql->fetch($monReq))
{
	if($captures[$mon['id']])
	{
		$pics = explode("|", $mon['pic']);
		$pic = $pics[0];
		$data[] = array
		(
			"id" => $mon['id'],
			"img" => "<img src=\"img/sprites/".$pic."\" title=\"".$mon['title']."\" alt=\"\" />",
			"name" => $mon['name'],
			"flavor" => $mon['flavor'],
		);
	}
	else
	{
		$data[] = array
		(
			"id" => $mon['id'],
			"img" => "&nbsp;",
			"name" => "???",
			"flavor" => "&nbsp;"
		);
	}
}

$data[6]['secretbuttfun'] = "PONIES AND PONIES AND PONIES AND PONIES...";

RenderTable($data, $headers);

pagefooter();

?>
