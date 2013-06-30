<?php
  require 'lib/common.php';
  require 'lib/threadpost.php';
  loadsmilies();

    $announce=$_REQUEST[announce];
    checknumeric($announce);

	// [Mega-Mario] This is currently useless. TODO: fix or nuke.
  if($act=$_POST[action])
  {
    $fid=$_POST[fid];
    if ($log)
	{
		$userid = $loguser['id'];
		$user = $loguser;
		if ($_POST['passenc'] !== md5($loguser['pass'].$pwdsalt))
			$err = 'Invalid token.';
			
		$pass = $_POST['passenc'];
	}
	else
	{
      $pass=md5($_POST[pass].$pwdsalt);

    if($userid=checkuser($_POST[name],$pass))
      $user=$sql->fetchq("SELECT * FROM users WHERE id=$userid");
    else
      $err="    Invalid username or password!<br>
".         "    <a href=forum.php?id=$fid>Back to forum</a> or <a href=newthread.php?id=$fid>try again</a>";

		$pass = md5($pass.$pwdsalt);
	}
  }
  else
  {
    $user=$loguser;
    $fid=$_GET[id];
  }
  checknumeric($fid);

  needs_login(1);

  if ($announce) {
    $type = "announcement";
    $typecap = "Announcement";
  }
  elseif ($_GET[ispoll]) {
    $type = "poll";
    $typecap = "Poll";
	$ispoll = 1;
  }
  else {
    $type = "thread";
    $typecap = "Thread";
	$ispoll = 0;
  }


  if ($announce && $fid==0)
	$forum = array('id' => 0, 'readonly' => 1);
  else
    $forum=$sql->fetchq("SELECT * FROM forums WHERE id=$fid AND id IN ".forums_with_view_perm());
	
if($act!="Submit" || $loguser[redirtype]==0){
  pageheader("New $type",$forum[id]);
  echo "<script language=\"javascript\" type=\"text/javascript\" src=\"tools.js\"></script>";
  $toolbar= posttoolbar();
	 
	if ($ispoll)
	{
		echo '<script type="text/javascript" src="jscolor/jscolor.js"></script>';
		echo '<script type="text/javascript" src="polleditor.js"></script>';
		$optfield = '<div><input type="text" name="opt[]" size=40 maxlength=40 value="%s"> - Color: <input class="color" name="col[]" value="%02X%02X%02X"> - <button class="submit" onclick="removeOption(this.parentNode);return false;">Remove</button></div>';
	}
}
  $tagsin="";
  $t=$sql->query("SELECT * FROM tags WHERE fid=$fid");
  while($tt=$sql->fetch($t)) {
    if($tagsin=="") $tagsin=
          "$L[TR]>
".        "  $L[TD1c]>$typecap tags:</td>
".        "  $L[TD2]>
";
    $tagsin.="<input type='checkbox' name='tag$tt[bit]' id='tag$tt[bit]' value='1' ".($_POST["tag$tt[bit]"]?"checked":"")."><label for='tag$tt[bit]'>$tt[name]</label> ";
  }
  if($tagsin!="") $tags.="</tr>";

  $forumlink="<a href=forum.php?id=$fid>Back to forum</a>";

  if(!$forum)
    forum_not_found();

    else if ($announce && !can_create_forum_announcements($fid))
    $err = "    You have no permissions to create announcements in this forum!<br>$forumLink";

//  else if($forum[minpowerthread]>$user[power]){
     else if (!can_create_forum_thread($forum)){

  $err="    You have no permissions to create threads in this forum!<br>$forumlink";
//    if(isbanned())
/*      $err="    You can't post when you are banned!<br>
".         "    $forumlink";
    else
      $err="    You can't post in this restricted forum!<br>
".         "    $forumlink";*/
  }

  else if($user[lastpost]>ctime()-30 && $act=='Submit' && !has_perm('ignore-thread-time-limit'))
      $err="    Don't post threads so fast, wait a little longer.<br>
".         "    $forumlink";

  //2007-02-19 //blackhole89 - table breach protection
  if($act=='Submit'){
    $title = $_POST['title'];
    $message =  $_POST['message'];
    if(($tdepth=tvalidate($message))!=0)
      $err="    This post would disrupt the board's table layout! The calculated table depth is $tdepth.<br>
".         "    $forumlink";
    if(strlen(trim(str_replace(" ","",$title)))<4)
      $err="    You need to enter a longer $type title.<br>
".         "    $forumlink";
    if($ispoll && (!isset($_POST['opt']) || count($_POST['opt']) < 2))
      $err="    You must add atleast two choices to your poll.<br>
".         "    $forumlink";
    else if($ispoll) {
      foreach ($_POST['opt'] as $id => $text)
        if(trim($text) == '' || $_POST['col'][$id] == '')
          $err="You must fill in all poll choices' fields.<br>
".             "$forumlink";
    }
  }

  $top="<a href=./>Main</a> - <a href=forum.php?id=$fid>$forum[title]</a> - New $type";

  $i=1;
  $icons=$sql->query('SELECT * FROM posticons ORDER BY id');
  while($icon=$sql->fetch($icons))
    $iconlist.=
          "      $L[INPr]=iconid value=$i> <img src=$icon[url]>&nbsp; &nbsp;".(!($i++%10)?'<br>':'')."
";
  $iconlist.=
          "      $L[INPr]=iconid value=0 checked> None&nbsp; &nbsp;
".        "      Custom: $L[INPt]=iconurl size=40 maxlength=100>
";

  if($err){
    if($loguser[redirtype]==1) pageheader("New $type",$forum[id]);
    print "$top - Error
".        "<br><br>
".        "$L[TBL1]>
".        "  $L[TD1c]>
".        "$err
".        "$L[TBLend]
";
  }elseif(!$act){
    if($ispoll){
      $pollin=
          "$L[TR]>
".        "  $L[TD1c]>Poll question:</td>
".        "  $L[TD2]>$L[INPt]=question size=100 maxlength=100 value=\"".htmlval($_POST[question])."\"></td>
".        "$L[TR]>
".        "  $L[TD1c]>Poll choices:</td>
".        "  $L[TD2]><div id=\"polloptions\">
".        "    ".sprintf($optfield, '', rand(0,255), rand(0,255), rand(0,255))."
".        "    ".sprintf($optfield, '', rand(0,255), rand(0,255), rand(0,255))."
".        "  </div>
".        "  $L[BTTn]=addopt onclick=\"addOption();return false;\">Add choice</button></td>
".        "$L[TR]>
".             "  $L[TD1c]>Options:</td>
".             "  $L[TD2]>$L[INPc]=multivote value=1 id=mv><label for=mv>Allow multiple voting</label> | $L[INPc]=changeable checked value=1 id=ch><label for=ch>Allow changing one's vote</label>
";
    }
    print "$top
".        "<br><br>
".        "<form action=newthread.php?ispoll=$ispoll method=post>
".        " $L[TBL1]>
".        "  $L[TRh]>
".        "    $L[TDh] colspan=2>$typecap</td>
";
    if(!$log)
    print "  $L[TR]>
".        "    $L[TD1c]>Username:</td>
".        "    $L[TD2]>$L[INPt]=name size=25 maxlength=25></td>
".        "  $L[TR]>
".        "    $L[TD1c]>Password:</td>
".        "    $L[TD2]>$L[INPp]=pass size=13 maxlength=32></td>
";
    else
    print "  $L[INPh]=name value=\"".htmlval($loguser[name])."\">
".        "  $L[INPh]=passenc value=\"".md5($loguser[pass].$pwdsalt)."\">
";
    print "  $L[TR]>
".        "    $L[TD1c]>$typecap title:</td>
".        "    $L[TD2]>$L[INPt]=title size=100 maxlength=100></td>
".        "  $L[TR]>
".        "    $L[TD1c]>$typecap icon:</td>
".        "    $L[TD2]>
".        "$iconlist
".        "    </td>
".$tagsin."
".$pollin."
".        "  $L[TR]>
".        "    $L[TD1c] width=120>Format:</td>
".        "    $L[TD2]>$L[TBL]>$L[TR]>$toolbar$L[TBLend]
".        "  $L[TR]>
".        "    $L[TD1c] width=120>Post:</td>
".        "    $L[TD2]>$L[TXTa]=message id='message' rows=20 cols=80></textarea></td>
".        "  $L[TR1]>
".        "    $L[TD]>&nbsp;</td>
".        "    $L[TD]>
".        "      $L[INPh]=fid value=$fid>
".        "      $L[INPh]=announce value=$announce>
".        "      $L[INPs]=action value=Submit>
".        "      $L[INPs]=action value=Preview>
".        // 2009-07 Sukasa: Newthread mood selector, just in the place I put it in mine
          "      $L[INPl]=mid>".moodlist()."
".        "      $L[INPc]=nolayout id=nolayout value=1 ".($_POST[nolayout]?"checked":"")."><label for=nolayout>Disable post layout</label>
".        "    </td>
".        " $L[TBLend]
".        "</form>
";
  }elseif($act=='Preview'){
    $_POST[title]  =stripslashes($_POST[title]);
    $_POST[message]=stripslashes($_POST[message]);

    $post[date]=ctime();
    $post[ip]=$userip;
    $post[num]=++$user[posts];
    $post[text]=$_POST[message];
    $post[mood] = (isset($_POST[mid]) ? (int)$_POST[mid] : -1); // 2009-07 Sukasa: Newthread preview
    $post[nolayout]=$_POST[nolayout];
    foreach($user as $field => $val)
      $post[u.$field]=$val;
    $post[ulastpost]=ctime();

    if($ispoll){
      $_POST[question]=stripslashes($_POST[question]);
      $numopts=$_POST[numopts];
      checknumeric($numopts);
      $pollprev="<br>$L[TBL1]>
".        "  $L[TR1]>
".        "    $L[TD1] colspan=2>".htmlval($_POST[question])."
";
      $pollin=
          "$L[TR]>
".        "  $L[TD1c]>Poll question:</td>
".        "  $L[TD2]>$L[INPt]=question size=100 maxlength=100 value=\"".htmlval($_POST[question])."\"></td>
".        "$L[TR]>
".        "  $L[TD1c]>Poll choices:</td>
".        "  $L[TD2]><div id=\"polloptions\">
";

      if (isset($_POST['opt']))
	  {
		  foreach ($_POST['opt'] as $id => $text)
		  {
			$text = htmlval(stripslashes($text));
			
			$color = stripslashes($_POST['col'][$id]);
			list($r,$g,$b) = sscanf(strtolower($color), '%02x%02x%02x');
			
			$pollin .= "    ".sprintf($optfield, $text, $r, $g, $b)."\n";
			$pollprev .= "$L[TR2]>$L[TD2]>{$text} $h$L[TD3]><img src=\"gfx/bargraph.php?z=1&n=1&r={$r}&g={$g}&b={$b}\">";

		  }
	  }
	  
      $pollin.="  </div>
".             "  $L[BTTn]=addopt onclick=\"addOption();return false;\">Add choice</button></td>
".             "$L[TR]>
".             "  $L[TD1c]>Options:</td>
".             "  $L[TD2]>$L[INPc]=multivote ".($_POST[multivote]?"checked":"")." value=1 id=mv><label for=mv>Allow multiple voting</label> | $L[INPc]=changeable ".($_POST[changeable]?"checked":"")." value=1 id=ch><label for=ch>Allow changing one's vote</label>
";
$pollprev.="$L[TBLend]";
    }

    print "$top - Preview
".        "$pollprev<br>
".        "$L[TBL1]>
".        "  $L[TRh]>
".        "    $L[TDh] colspan=2>Post preview
".        "$L[TBLend]
".         threadpost($post,0)."
".        "<br>
".        "<form action=newthread.php?ispoll=$ispoll method=post>
".        " $L[TBL1]>
".        "  $L[TRh]>
".        "    $L[TDh] colspan=2>$typecap</td>
".        "  $L[TR]>
".        "    $L[TD1c]>$typecap title:</td>
".        "    $L[TD2]>$L[INPt]=title size=100 maxlength=100 value=\"".htmlval($_POST[title])."\"></td>
".$tagsin."
".$pollin."
".        "  $L[TR]>
".        "    $L[TD1c] width=120>Format:</td>
".        "    $L[TD2]>$L[TBL]>$L[TR]>$toolbar$L[TBLend]
".        "  $L[TR]>
".        "    $L[TD1c] width=120>Post:</td>
".        "    $L[TD2]>$L[TXTa]=message id='message' rows=10 cols=80>".htmlval($_POST[message])."</textarea></td>
".        "  $L[TR1]>
".        "    $L[TD]>&nbsp;</td>
".        "    $L[TD]>
".        "      $L[INPh]=name value=\"".htmlval(stripslashes($_POST[name]))."\">
".        "      $L[INPh]=passenc value=\"$pass\">
".        "      $L[INPh]=fid value=$fid>
".        "      $L[INPh]=iconid value=$_POST[iconid]>
".        "      $L[INPh]=iconurl value=$_POST[iconurl]>
".        "      $L[INPh]=announce value=$announce>
".        "      $L[INPs]=action value=Submit>
".        "      $L[INPs]=action value=Preview>
".        // 2009-07 Sukasa: Newthread mood selector, just in the place I put it in mine
          "      $L[INPl]=mid>".moodlist()."
".        "      $L[INPc]=nolayout id=nolayout value=1 ".($post[nolayout]?"checked":"")."><label for=nolayout>Disable post layout</label>
".        "    </td>
".        " $L[TBLend]
".        "</form>
";
  }elseif($act=='Submit'){
    if(!($iconurl=$_POST[iconurl]))
      $iconurl=$sql->resultq("SELECT url FROM posticons WHERE id=".(int)$_POST[iconid]);

    checknumeric($nolayout);

    $iconurl=addslashes($iconurl);

    $user=$sql->fetchq("SELECT * FROM users WHERE id=$userid");
    $user[posts]++;

    $tagsum=0;
    for($i=0;$i<32;++$i) if($_POST["tag$i"]) $tagsum|=(1<<$i);
    
    $mid=(isset($_POST[mid]) ? (int)$_POST[mid] : -1);

    $sql->query("UPDATE users SET posts=posts+1,threads=threads+1,lastpost=".ctime()." "
               ."WHERE id=$userid");
    $sql->query("INSERT INTO threads (title,forum,user,lastdate,lastuser,icon,tags,announce,closed) "
               ."VALUES ('$_POST[title]',$fid,$userid,".ctime().",$userid,'$iconurl',$tagsum,$announce,$announce)");
    $tid=$sql->insertid();
    $sql->query("INSERT INTO posts (user,thread,date,ip,num,mood,nolayout,announce) "
               ."VALUES ($userid,$tid,".ctime().",'$userip',$user[posts],$mid,$nolayout,$announce)");
    $pid=$sql->insertid();
    $sql->query("INSERT INTO poststext (id,text) VALUES ($pid,'$message')");
if (!$announce)   {
   $sql->query("UPDATE forums SET threads=threads+1,posts=posts+1,lastdate=".ctime().",lastuser=$userid,lastid=$pid "
               ."WHERE id=$fid");
}
    $sql->query("UPDATE threads SET lastid=$pid WHERE id=$tid");

    if($ispoll)
    {
      $sql->query("INSERT INTO polls (id,question,multivote,changeable) VALUES ($tid,'{$_POST['question']}','{$_POST['multivote']}','{$_POST['changeable']}')");
	  
      foreach ($_POST['opt'] as $id => $text)
	  {
	    $color = stripslashes($_POST['col'][$id]);
		list($r,$g,$b) = sscanf(strtolower($color), '%02x%02x%02x');
		
        $sql->query("INSERT INTO polloptions (`poll`,`option`,r,g,b) VALUES ($tid,'{$text}',".(int)$r.",".(int)$g.",".(int)$b.")");
	  }
    }

    // bonus shit
    $c = rand(250,750);
    if (!$announce) $sql->query("UPDATE `usersrpg` SET `spent` = `spent` - '$c' WHERE `id` = '$userid'");

    $chan = $sql->resultp("SELECT chan FROM announcechans WHERE id=?",array($forum['announcechan_id']));

if ($announce) {
  $viewlink = "thread.php?announce=".$forum['id'];
  $shortlink = "a=".$forum['id'];
  $bonus = "";
}
else {
  $viewlink = "thread.php?id=$tid";
  $shortlink = "t=$tid";
  $bonus = "    Posted! (Gained $c bonus coins)<br>";
}

if ($announce && $forum['id']==0) {
     sendirc("{irccolor-base}New $type by {irccolor-name}".get_irc_displayname()."{irccolor-url}: {irccolor-name}".stripslashes($_POST[title])."{irccolor-base} - {irccolor-url}{boardurl}?$shortlink{irccolor-base}",$chan);
}
else if ($announce) {
     sendirc("{irccolor-base}New forum $type by {irccolor-name}".get_irc_displayname()."{irccolor-base} in {irccolor-title}$forum[title]{irccolor-url}: {irccolor-name}".stripslashes($_POST[title])."{irccolor-base} - {irccolor-url}{boardurl}?$shortlink{irccolor-base}",$chan);  
}
else {
     
     sendirc("{irccolor-base}New $type by {irccolor-name}".get_irc_displayname()."{irccolor-base} in {irccolor-title}".$forum[title]."{irccolor-url}: {irccolor-name}".stripslashes($_POST[title])."{irccolor-base} - {irccolor-url}"."{boardurl}?$shortlink{irccolor-base}",$chan);

}


if($loguser[redirtype]==0){ //Classic
    print "$top - Submit
".        "<br><br>
".        "$L[TBL1]>
".        "  $L[TD1c]>
".        "  $bonus
".        "    ".redirect($viewlink,"the $type")."
".        "$L[TBLend]
";
} else { //Modern
  redir2($viewlink,$c);
}
  }

  pagefooter();
?>
