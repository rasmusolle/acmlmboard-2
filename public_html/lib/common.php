<?php
  require "lib/function.php";

  //Enforce SSL if enabled.
  if(!isssl() && $config['forcessl']==true){
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://".getenv('HTTP_HOST')."".getenv('REQUEST_URI')."");
    die();
  }
  header("Content-type: text/html; charset=utf-8");
  
  //[Scrydan] Added these three variables to make editing quicker.
  $boardprog = "Acmlm, Emuz, <a href='credits.php'>et al</a>.";
  $abdate    = "01/20/2020";
  $abversion = "2.5.4 <span style=\"color: #BCDE9A; font-style: italic;\">Development</span><span style=\"color: #EE4444; font-weight: bold;\"> UNSTABLE</span>";

  $userip  = $_SERVER['REMOTE_ADDR'];
  $userfwd = addslashes(getenv('HTTP_X_FORWARDED_FOR')); //We add slashes to that because the header is under users' control
  $url     = getenv("SCRIPT_NAME");
  if($q = getenv("QUERY_STRING"))
    $url.="?$q";

	require "lib/login.php";

	// Anything useful globally, right here
	$misc = $sql->fetchq("SELECT lockdown, views, botviews, maxpostsday, maxpostshour, maxusers, attention, attentiontitle, sqlversion FROM misc");
  
	if ($misc['lockdown']) {
		//lock down
		if (has_perm('bypass-lockdown')) {
			print "<h1><font color=\"red\"><center>LOCKDOWN!! LOCKDOWN!! LOCKDOWN!!</center></font></h1>";
		} else { //Everyone else gets the wonderful lockdown page.
			$misc['lockdowntext'] = $sql->resultq("SELECT lockdowntext FROM misc");
			include "lib/locked.php";
			die;
		}
	}

  if(!$log)
   {
    $loguser               = array();
    $loguser['id']         = 0;	
    $loguser['group_id']   = 1;
    $loguser['tzoff']      = 0;
    $loguser['timezone']   = "UTC";
    $loguser['fontsize']   = $defaultfontsize;    //2/22/2007 xkeeper - guests have "normal" by default, like everyone else
    $loguser['dateformat'] = "m-d-y";
    $loguser['timeformat'] = "h:i A";
    $loguser['signsep']    = 0;
    $loguser['theme']      = $defaulttheme;
    if(strpos($_SERVER['HTTP_USER_AGENT'], "MSIE 6.0") !== false)
     $loguser['theme'] = "minerslament";
    $loguser['blocksprites']=1;
   }
  
  $flocalmod=$sql->fetchq("SELECT `uid` FROM `forummods`");
  if(checkvar('loguser','id') == checkvar('flocalmod','uid'))
   {
    $loguser['modforums']=array();
    $modf=$sql->query("SELECT `fid` FROM `forummods` WHERE `uid`='$loguser[id]'");
   while($m=$sql->fetch($modf))
    {
     $loguser['modforums'][$m['fid']]=1;
    }
   }

  require "lib/timezone.php";
  dobirthdays(); //Called here to account for timezone bugs.

  if(checkvar('loguser','ppp') < 1)
   $loguser['ppp'] = 20;
  if(checkvar('loguser','tpp') < 1)
   $loguser['tpp'] = 20;

   // Moved here since the ipbans page would call pageheader() with themes uninitialized otherwise
   //[KAWA] ABXD-style theme system
   $themelist = unserialize(file_get_contents("themes_serial.txt"));

   //Config definable theme override
   if($config['override_theme'] && !has_special_perm("bypass-theme-override")) //If defined in config & current user does not have the special bypass perm; use the theme defined.
    {
      $theme = $config[override_theme];
    }
   elseif (isset($_GET['theme']))
    {
      $theme = $_GET['theme'];
    }
   else 
    {
      $theme = $loguser['theme'];
    }

  if(is_file("css/".$theme.".css"))
   {
    //try CSS first
    $themefile = $theme.".css";
   }
  elseif(is_file("css/".$theme.".php"))
   {
    //then try PHP
    $themefile = $theme.".php";
   }
  else //then fall back to Standard
   {
    $theme = $themelist[0][1];
    $themefile = $theme.".css";
   }

  //2007-02-19 blackhole89 - needs to be here because it requires loguser data
  require "lib/ipbans.php";
  
  //Unban users whose tempbans have expired. - SquidEmpress
  $defaultgroup = $sql->resultq("SELECT id FROM `group` WHERE `default`=1");
  $sql->query('UPDATE users SET group_id='.$defaultgroup.', title="", tempbanned="0" WHERE tempbanned<'.ctime().' AND tempbanned>0');

  $dateformat = "$loguser[dateformat] $loguser[timeformat]";

  $bota=$sql->query("SELECT `bot_agent` FROM `robots`");
  while($robots=$sql->fetch($bota)){
    $bots[]=$robots['bot_agent'];
  }
  $bot = 0;

  if (str_replace($bots, "x", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT'])
   {
    $bot = 1;
   }
  if ($bot)
   {
    load_bot_permset();
   }
  if(substr($url, 0, strlen("$config[path]rss.php")) != "$config[path]rss.php")
   {
    $sql->query("DELETE FROM `guests` WHERE `ip`='$userip' OR `date`<".(ctime()-$config['oldguest']));
   if($log)
    {
    //AB-SPECIFIC
    if(has_perm('track-ip-change') && ($userip != ($oldip=$loguser['ip'])))
     {
      sendirc("{irccolor-base}".get_irc_groupname($loguser['group_id'])." {irccolor-name}".($loguser['displayname'] ? $loguser['displayname'] : $loguser['name'])."{irccolor-base} changed IPs from {irccolor-no}$oldip{irccolor-base} to {irccolor-yes}$userip{irccolor-base}", $config['staffchan']);
     }
  if(str_replace("sprites.php?","",$url)!=$url || isset($nourltracker)){ //Do not update last url if the clicked link was a sprite
    $sql->query("UPDATE `users` SET `lastview`=".ctime().",`ip`='$userip', `ipfwd`='$userfwd', `ipbanned`='0' WHERE `id`='$loguser[id]'");
} else {
    $sql->query("UPDATE `users` SET `lastview`=".ctime().",`ip`='$userip', `ipfwd`='$userfwd', `url`='".(isssl() ? "!" : "").addslashes($url)."', `ipbanned`='0' WHERE `id`='$loguser[id]'");
}
    }
   else
    {
     $sql->query('INSERT INTO `guests` (`date`, `ip`, `url`, `useragent`, `bot`) VALUES ('.ctime().",'$userip','".(isssl() ? "!" : "").addslashes($url)."', '". addslashes($_SERVER['HTTP_USER_AGENT']) ."', '$bot')");
    }
    
    //[blackhole89]
   if($config['log'] >= '5')
    {
    $postvars="";
    foreach($_POST as $k=>$v)
     {
     if($k=="pass" || $k=='pass2')
       $v="(snip)";
      $postvars.="$k=$v ";
     }
     @$sql->query("INSERT DELAYED INTO `log` VALUES(UNIX_TIMESTAMP(),'$userip','$loguser[id]','".addslashes($_SERVER['HTTP_USER_AGENT'])." :: ".addslashes($url)." :: $postvars')");
    }

    $ref  = (isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER'] : "");
    $ref2 = substr($ref, 0, 25);
   if($ref && !strpos($ref2, $config['address']))
    {
     $sql -> query("INSERT INTO `ref` SET `time`='".ctime()."', `userid`='$loguser[id]', `urlfrom`='".addslashes($ref)."',
                                          `urlto`='".addslashes($url)."', `ipaddr`='".$_SERVER['REMOTE_ADDR']."'");
    }

	//--
	if (!$bot) {
		$sql->query("UPDATE `misc` SET `views`=`views`+1");
		++$misc['views'];
	} else {
		$sql->query('UPDATE `misc` SET `botviews`=`botviews`+1');
		++$misc['botviews'];
	}

	if (($misc['views'] + 100) % 1000000 <= 200) {
		$sql->query("INSERT INTO `views` SET `view` = {$misc['views']}, `user`='{$loguser['id']}', `time`='".ctime()."'");
		if (($misc['views'] + 10) % 1000000 <= 20) {
			if (!$bot) {
				sendirc("{irccolor-base}View {irccolor-title}{$misc['views']}{irccolor-base} by ".($log ? "{irccolor-name}".get_irc_displayname()."":"{irccolor-name}$userip")."{irccolor-base}");
			}
		}
	}
	//--
  
	$count = $sql->fetchq("	SELECT
								(SELECT COUNT(*) FROM users) u,
								(SELECT COUNT(*) FROM threads) t,
								(SELECT COUNT(*) FROM posts) p");
    $date       = gmdate("m-d-y",ctime());
    $sql->query("REPLACE INTO `dailystats` (`date`, `users`, `threads`, `posts`, `views`)
                 VALUES ('$date', '$count[u]', '$count[t]', '$count[p]', '{$misc['views']}')");

     //2/21/2007 xkeeper - adding, uh, hourlyviews
    $sql->query("INSERT INTO `hourlyviews` (`hour`, `views`)
                 VALUES (".floor(ctime()/3600).",1)
                 ON DUPLICATE KEY UPDATE `views`=`views`+1");
   }
   
  if($config['override_logo'] && !has_special_perm("bypass-logo-override")) //Config override for the logo file
    $logofile = $config[override_logo];
  elseif(is_file("theme/".$theme."/logo.png"))
   $logofile = "theme/".$theme."/logo.png";
  else
   $logofile = $defaultlogo;
  
  $rpgimageset = '';

  if($config['userpgnumdefault']) $rpgimageset = "gfx/rpg/";

  $statusimageset = '';

  if($config['userpgnum'] || $config['alwaysshowlvlbar'])
  {
    if(is_file("theme/".$theme."/rpg/0.png")) $rpgimageset="theme/".$theme."/rpg/";
  }

 $statusimageset = '';

  /*if($config['userpgnum'] || $config['alwaysshowlvlbar'])
  {*/
    if(is_file("theme/".$theme."/status/new.png")) $statusimageset="theme/".$theme."/status/";
  //}

  $feedicons="";

   /*
    Salvaged from "Xkeeper's Nifty Page-o-Hacks". Why? I don't really know, however it's a nice bit of code
    for 'just in case' purposes I guess. Basically it'll get axed when we clean up the other fragments anyway.
    -Emuz

   if(strstr($url,"UNION%20SELECT") && $loguser[power]<3) {
     $sql->query("INSERT INTO ipbans VALUES ('$REMOTE_ADDR',1,'','automatic','UNION SELECT')");
     print "(insert sound of something blowing up here)";
    die();
   } 
   */
   
	// New cookie message display
	$cookiemsg = "";
	if (isset($_COOKIE['wndmsg']) && $_COOKIE['wndmsg']) {
		setcookie('wndmsg', null);
		setcookie('wndtitle', null);
		$cookiemsg = infownd(htmlspecialchars($_COOKIE['wndtitle']), securityfilter($_COOKIE['wndmsg']));
	}
   

   //2/21/2007 xkeeper - todo: add $forumid attribute (? to add "forum user is in" and markread links
   // also added number_format to views
   // also changed the title to be "pagetitle - boardname" and not vice-versa
  function pageheader($pagetitle="", $fid=0, $metadata="Default")
   {
    global $L, $dateformat, $sql, $log, $loguser, $misc, $sqluser, $boardtitle, $extratitle, $boardlogo, $homepageurl, $themefile,
           $logofile, $url, $config, $feedicons, $favicon, $showonusers, $count, $lastannounce, $lastforumannounce, $inactivedays;

   if (ini_get("register_globals"))
    {
     print "<span style=\"color: red;\"> Warning: register_globals is enabled.</style>";
    }
    // this is the only common.php location where we reliably know $fid.
   if($log)
    {
     $sql->query("UPDATE `users` SET `lastforum`='$fid' WHERE `id`='$loguser[id]'");
    }
   else
    {
     $sql->query("UPDATE `guests` SET `lastforum`='$fid' WHERE `ip`='$_SERVER[REMOTE_ADDR]'");
    }
    $timezone   = new DateTimeZone($loguser['timezone']);
    $tzoff      = $timezone->getOffset(new DateTime("now"));
    $themefile .= "?tz=$tzoff"; // "minover" removed, appears to be unused? [Epele]

   if($pagetitle)
     $pagetitle .= " - ";

	//--
	// Attention box
	$ae = has_perm("edit-attentions-box") ? "(<a href='editattn.php'>edit</a>)" : "";
	if ($misc['attention']) {
		$extratitle = "
		$L[TBL1] width='100%' align='center'>
			$L[TRh]>$L[TDh]>{$misc['attentiontitle']} $ae</td></tr>
			$L[TR2] align='center'>$L[TDs]>{$misc['attention']}</td></tr>
		$L[TBLend]";
	} else {
		$extratitle = $ae;
	}
	// Internal config version check.
	// This value should be updated whenever a new config setting is added. [Epele]
	if(has_perm("manage-board") && $config['version']!=2) $extratitle.="<br><b>Notice: Configuration file mismatch detected.</b>";
	//--

   if($extratitle)
    {
     $boardlogo = "
             $L[TBL] width=100%>
               $L[TRc]>
                 $L[TD] style=\"border:none!important\" valign=\"center\"><a href=\"$homepageurl\"><img src=\"$logofile\"></a></td>
                 $L[TD] style=\"border:none!important\" valign=\"center\" width=\"300\">
                   $extratitle
                 </td>
             $L[TBLend]";
    }

    $feedicons .= feedicon("img/rss.png", "rss.php");

   if(isssl() && $config['showssl'])
    {
     $ssllnk = "<img src=\"img/sslon.gif\" title=\"SSL enabled\">";
    }
   else if(!$config['showssl'])
   {
    $ssllnk = "";
   }
   else
    {
     $ssllnk = "<a href=\"$config[sslbase]$url\" title=\"View in SSL mode\"><img border=\"0\" src=\"img/ssloff.gif\"></a>";
    }
    
   if ($log)
    {
     $radar = build_postradar();
    }
    
    include("lib/sprites.php");
    
   if ($log)
    {
     $logbar                = $loguser;
     $logbar['showminipic'] = 1;
    }
   if ($metadata!="Default"){
     $mdata="<meta name='description' content=\"".htmlspecialchars($metadata)."\"><meta name='keywords' content=\"".$config['metakey']."\">";
   } else {
     $mdata=$config['meta'];
   }
    print "<!DOCTYPE html>
      <html>
      <head>
      <title>$pagetitle$boardtitle</title>
      $mdata
      <link rel=\"icon\" type=\"image/png\" href=\"$favicon\">
      <link rel=\"stylesheet\" href=\"css/global.css\">
      <link rel=\"stylesheet\" href=\"css/$themefile\">
      <link href=\"lib/prettify/sunburst.css\" type=\"text/css\" rel=\"stylesheet\" />
      <script type=\"text/javascript\" src=\"lib/prettify/prettify.js\"></script>
      </head>
      <body style=\"font-size:$loguser[fontsize]%\" onload=\"prettyPrint()\">$dongs
      $L[TBL1]>
        $L[TRT2c]>
        $L[TD1c] colspan=\"3\">$boardlogo</td>
        </tr>
        $L[TR2c]>
          $L[TD]>
          <div style=\"width: 150px\">Views: 
          <span title=\"And ".number_format($misc['botviews'])." views by search engine spiders.\">".number_format($misc['views'])."</span></div></td>
          $L[TD] width=\"100%\"><span style=\"float:right\">$feedicons$ssllnk</span>
            <a href=\"./\">Main</a>
          | <a href=\"faq.php\">FAQ</a>
          ".(has_perm("use-uploader") ? " | <a href=\"./uploader\">Uploader</a>" : "")."
          | <a href=\"irc.php\">IRC chat</a>
          | <a href=\"memberlist.php\">Memberlist</a>
          | <a href=\"activeusers.php\">Active users</a>
          | <a href=\"thread.php?time=86400\">Latest posts</a>
          ".(has_perm("view-calendar") ? " | <a href=\"calendar.php\">Calendar</a>" : "")."
          | <a href=\"stats.php\">Stats</a>
          | <a href=\"ranks.php\">Ranks</a>
          | <a href=\"online.php\">Online users</a>
          | <a href=\"search.php\">Search</a>
          </td>
          $L[TD]><div style=\"width: 150px\">".cdate($dateformat,ctime())."</div></td>
        $L[TR1c]>
          $L[TD] colspan=\"3\">
            ".($log ? userlink($logbar) : "Guest").": ";
			
		

  if($log)
    {
     //2/25/2007 xkeeper - framework laid out. Naturally, the SQL queries are a -mess-. --;
     $pmsgs = $sql->fetchq("SELECT `p`.`id` `id`, `p`.`date` `date`, ".userfields('u','u')."
                            FROM `pmsgs` `p`
                            LEFT JOIN `users` `u` ON `u`.`id`=`p`.`userfrom`
                            WHERE `p`.`userto`='$loguser[id]'
                            ORDER BY `date` DESC LIMIT 1");

     $unreadpms = $sql->resultq("SELECT COUNT(*) FROM `pmsgs` WHERE `userto`='$loguser[id]' AND `unread`=1 AND `del_to`='0'");
     $totalpms  = $sql->resultq("SELECT COUNT(*) FROM `pmsgs` WHERE `userto`='$loguser[id]' AND `del_to`='0'");

 
  if($unreadpms)
     {
      $status    = rendernewstatus("n");
      $unreadpms = " ($unreadpms new)";
     }
    else
     {
      $status    = "";
      $unreadpms = "";
     }
   //Starts code for the classic PM box.
    if ($config['classicpms'] && has_perm('view-own-pms'))
    {
    if($totalpms>0)
      $lastmsg="<br>
".      "      <font class=sfont><a href=showprivate.php?id=$pmsgs[id]>Last message</a> from ".userlink($pmsgs,'u').' on '.cdate($dateformat,$pmsgs[date]).'.</font>';
    else
      $lastmsg='';

    $oldpmsgbox=
        "$L[TBL1]>
".      "  $L[TRh]>
".      "    $L[TDh] colspan=2>Private Messages</td>
".      "  $L[TR]>
".      "    $L[TD1] width=17>$status</td>
".      "    $L[TD2]>
".      "      <a href=private.php>Private messages</a> -- You have $totalpms private message".($totalpms!=1?'s':'')."$unreadpms.$lastmsg
".      "  $L[TBLend]
".      "  <br>
";
  }
  else $oldpmsgbox ='';
      
    if (!$config['disablemodernpms'] && has_perm('view-own-pms'))
     {
     if ($unreadpms)
      {
       $pmimage = "gfx/pm.png";
      }
     else
      {
       $pmimage = "gfx/pm-off.png";
      } 
      $pmsgbox = "<a href=\"private.php\"><img src=\"$pmimage\" border=\"0\" alt=\"Private messages\" title=\"Private message\"></a> $unreadpms | ";
     }
    else
     {
      $pmsgbox = "";
     }
    }
    
    if(isset($pmsgbox)) print $pmsgbox;

    //mark forum read
    checknumeric($fid);
    if($fid)
      $markread=array("url" => "index.php?action=markread&fid=$fid", "title" => "Mark forum read");
    else
      $markread=array("url" => "index.php?action=markread&fid=all", "title" => "Mark all forums read");

    $userlinks = array();
    $ul = 0;
	

	if (!$log)
	{
		if (has_perm("register")) 
		  $userlinks[$ul++] = array('url' => "register.php", 'title' => 'Register');
		if (has_perm("view-login")) 
		  $userlinks[$ul++] = array('url' => "login.php", 'title' => 'Login');
	}
	else
	{
		if (has_perm("logout")) {
			$userlinks[$ul++] = array('url' => "javascript:document.logout.submit()", 'title' => 'Log out');
			print '
			<form method="POST" name="logout" action="login.php" style="display: none">
				'.auth_tag("weird").'
				<input type="hidden" name="action" value="logout">
			</form>';
		}
	}
    if (has_perm("update-own-profile")) 
      $userlinks[$ul++] = array('url' => "editprofile.php", 'title' => 'Edit profile');
    if (has_perm("post-radar")) 
      $userlinks[$ul++] = array('url' => "postradar.php", 'title' => 'Post radar');
    if (has_perm("view-favorites")) 
      $userlinks[$ul++] = array('url' => "forum.php?fav", 'title' => 'Favorite threads');
    if (has_perm("view-own-sprites")) 
      $userlinks[$ul++] = array('url' => "sprites.php", 'title' => 'My sprites');
    if (has_perm("update-own-moods")) 
      $userlinks[$ul++] = array('url' => "mood.php", 'title' => 'Edit mood avatars');
    if (has_perm("use-item-shop")) 
      $userlinks[$ul++] = array('url' => "shop.php", 'title' => 'Item shop');
    if (has_perm("view-acs-calendar")) 
      $userlinks[$ul++] = array('url' => "frank.php", 'title' => 'Rankings');
	if (has_perm('manage-board'))
	  $userlinks[$ul++] = array('url' => 'management.php', 'title' => 'Management');
    if (has_perm("mark-read")) 
      $userlinks[$ul++] = $markread;

    $c = 0;
	foreach ($userlinks as $k => $v) {
		if ($c++ > 0) print " | ";
		print "<a href=\"{$v['url']}\">{$v['title']}</a>";
		//print (isset($v['html']) ? $v['html'] : );
	}
	 
    if (isset($radar))
     {
      print " 
             $L[TR]>
               $L[TD1c] colspan=3>
                 $radar
			  </td>
			</tr>";
     }
	 
	 print "
			$L[TBLend]
			<br>";

     $hiddencheck  = "AND `hidden`='0' ";
    if (has_perm('view-hidden-users'))
     {
      $hiddencheck = "";
     }

    if($fid)
     {
      $onusers = $sql->query("SELECT ".userfields().", `lastpost`, `lastview`, `minipic`, `hidden`
                              FROM `users`
                              WHERE (`lastview` > ".(ctime()-300)." OR `lastpost` > ".(ctime()-300).") $hiddencheck AND `lastforum`='$fid'
                              ORDER BY `name`");
      $onuserlist  = "";
      $onusercount = 0;
     while($user   = $sql->fetch($onusers))
      {
       $user['showminipic'] = 1;
       $onuserlog   = ($user['lastpost'] <= $user['lastview']);
       $offline1          = ($onuserlog ? "":"(");
       $offline2          = ($onuserlog ? "":")");
       $onuserlist .= ($onusercount ? ", " : "") . $offline1 . ($user['hidden'] ? "(" . userlink($user) . ")" : userlink($user)) . $offline2;
       $onusercount++;
      }
      
      $fname      = $sql->resultq("SELECT `title` FROM `forums` WHERE `id`='$fid'");
      $onuserlist = "$onusercount user".($onusercount != 1 ? "s" : "")." currently in $fname".($onusercount>0? ": " : "").$onuserlist;
      $numguests = $numbots = 0;
      //[Scrydan] Changed from the commented code below to save a query.
      $onlineguests = $sql->query("SELECT bot FROM `guests` WHERE ".(isset($fid)?"`lastforum`='$fid' AND ":"")." `date` > '".(ctime()-300)."'");
     while($chkonline = $sql->fetch($onlineguests))
      {
       if ($chkonline['bot'] == 1){ $numbots++; } else { $numguests++; }
      }

     if ($numguests>=1){ $onuserlist .= " | $numguests guest".($numguests != 1 ? "s": ""); }
     if ($numbots>=1){   $onuserlist .= " | $numbots bot".($numbots != 1 ? "s": ""); }

      print "$L[TBL1]>
               $L[TR1]>
               $L[TD1c]>$onuserlist
              </td>
              </tr>
			 $L[TBLend]
			 <br>";
     }
    else if ($showonusers)
     { 
      //[KAWA] Copypastadaption from ABXD, with added activity limiter.
      $birthdaylimit = 86400 * $inactivedays;
      $rbirthdays = $sql->query("SELECT `birth`, ".userfields()."
                                 FROM `users`
                                 WHERE `birth` LIKE '".date('m-d')."%' AND `lastview` > ".(time()-$birthdaylimit)." ORDER BY `name`");
      $birthdays = array();
     while($user = $sql->fetch($rbirthdays))
      {
       $b = explode('-',$user['birth']);
       if($b['2'] <= 0 && $b['2'] > -2) $p = "";
       else $p = "(";
       //Patch to fix 2 digit birthdays. Needs retooled to a modern datetime system. -Emuz
       if($b['2'] <= 99 && $b['2'] > 15) $y = date("Y") - ($b['2'] + 1900).")";
       else if($b['2'] <= 14 && $b['2'] > 0) $y = date("Y") - ($b['2'] + 2000).")";
       else if($b['2'] <= 0 && $b['2'] > -2) $y = "";
       else $y = date("Y") - $b[2].")";
       $birthdays[] = userlink($user)." ".$p."".$y;
      }
     $birthdaybox=""; // Setting this blank to avoid PHP errors.
     if(count($birthdays))
      {
       $birthdaystoday = implode(", ", $birthdays);
       $birthdaybox="
        $L[TR1c]>
        $L[TD2c]>
        Birthdays today: $birthdaystoday";
      }

      $count['d'] = $sql->resultq("SELECT COUNT(*) FROM `posts` WHERE `date` > '".(ctime()-86400)."'");
      $count['h'] = $sql->resultq("SELECT COUNT(*) FROM `posts` WHERE `date` > '".(ctime()-3600)."'");
      $lastuser=$sql->fetchq("SELECT ".userfields()." FROM `users` ORDER BY `id` DESC LIMIT 1");

      $hiddencheck  = "AND `hidden`='0' ";
     if (has_perm('view-hidden-users'))
      {
       $hiddencheck = "";
      }

      $onusers = $sql->query("SELECT ".userfields().", `lastpost`, `lastview`, `minipic`, `hidden` FROM `users`
                           WHERE (`lastview` > ".(ctime()-300)." OR `lastpost` > ".(ctime()-300).") $hiddencheck ORDER BY `name`");
      $onuserlist  = "";
      $onusercount = 0;
     while($user=$sql->fetch($onusers))
      {
       $user['showminipic'] = 1;
       $onuserlog = ($user['lastpost'] <= $user['lastview']);
       $offline1=($onuserlog ? "" : "(");
       $offline2=($onuserlog ? "" : ")");
       $onuserlist.=($onusercount? ", ": "").$offline1.($user['hidden'] ? '('.userlink($user).')' : userlink($user)).$offline2;
       $onusercount++;
      }

	  
	// Update records
	if ($count['d'] > $misc['maxpostsday']) {
		$sql->query("UPDATE `misc` SET `maxpostsday` = '{$count['d']}', `maxpostsdaydate` = '".ctime()."'");
	}
	if ($count['h'] > $misc['maxpostshour']) {
		$sql->query("UPDATE `misc` SET `maxpostshour` = '{$count['h']}', `maxpostshourdate` = '".ctime()."'");
	}
	if ($onusercount > $misc['maxusers']) {
		$sql->prepare("UPDATE `misc` SET `maxusers` = ?, `maxusersdate` = ?, `maxuserstext` = ?", array($onusercount, ctime(), $onuserlist));
	}

      $onuserlist="$onusercount user".($onusercount!=1?'s':'').' online'.($onusercount>0?': ':'').$onuserlist;
  
      //[Scrydan] Changed from the commented code below to save a query.
      //NOTE: THIS CODE IS USED TWICE, WE SHOULD FIND A WAY TO REUSE THIS INSTEAD OF ADDING MORE LINES!
     $onlineguests = $sql->query("SELECT * FROM `guests` WHERE ".(isset($fid)?"":"`lastforum`='$fid' AND ")."`date` > '".(ctime()-300)."'");
     $numbots = $numguests = 0; //Initialise to reduce error notices. [Epele]
     while($chkonline = $sql->fetch($onlineguests)){
      if ($chkonline['bot'] == 1){ $numbots++; } else { $numguests++; }
     }

     if ($numguests>=1) { $onuserlist .= " | $numguests guest".($numguests != 1 ? "s": ""); }
     if ($numbots>=1)   { $onuserlist .= " | $numbots bot".($numbots != 1 ? "s": ""); }
      
      
      $activeusers   = $sql->resultq("SELECT COUNT(*) FROM `users` WHERE `lastpost` > '". (ctime() - 86400) ."'");
      $activethreads = $sql->resultq("SELECT COUNT(*) FROM `threads` WHERE `lastdate` > '". (ctime() - 86400) ."'");
      if(!isset($oldpmsgbox)) $oldpmsgbox=''; //Error catch for logged out users.
      print "
	     $L[TBL1]>$birthdaybox
           $L[TR]>
             $L[TD1]>
               $L[TBL] width=\"100%\">
                 $L[TR]>
                   $L[TDn] width=\"250\"></td>
                   $L[TDnc]>
                     <span class=\"white-space:nowrap\"> <!--This was <nobr>, note: find class instead of this-->
                       $count[t] threads and $count[p] posts total | 
                       $count[d] new posts today, $count[h] last hour.<br>
                       $activeusers active users and $activethreads active threads during the last day.<br> 
                     </span>
                   </td>
                   $L[TDnr] width=\"250\">
                    $count[u] registered users<br>
                    Newest: ".userlink($lastuser)."
                   </td>
                 </tr>
               $L[TBLend]
           $L[TR]>
             $L[TD2c]>
               $onuserlist
			 </td>
		   </tr>
		 $L[TBLend]
		 <br>
          $oldpmsgbox";
     }
     define('HEADER_PRINTED', true);
   }

  function pagestats()
   {
    global $start,$L,$sql;
    $time = usectime() - $start;
    print "<br>
           $L[TBL2]>
             $L[TD1]>
               <center>
                 ".sprintf("Page rendered in %1.3f seconds. (%dKB of memory used)", $time, memory_get_usage(false)/1024)."<br>
                 MySQL - queries: $sql->queries, rows: $sql->rowsf/$sql->rowst, time: ".sprintf("%1.3f seconds.",$sql->time)."<br>
               </center>
           $L[TBLend]";
   }

  function miscbar()
   {
    global $L;
    //pagestats();
    print "<br>$L[TBL2]>$L[TRc]>$L[TD2l]><center><img src=\"img/poweredbyacmlm.PNG\">$L[TBLend]";
   }
   
  function noticemsg($name, $msg)
   {
  		print "<table cellspacing=\"0\" class=\"c1\">
".        " <tr class=\"h\">
".        "  <td class=\"b h\" align=\"center\">$name
".        " <tr>
".        "  <td class=\"b n1\" align=\"center\">
".        "    $msg
".        "</table>
".        "<br>
";
   }

  function error($name, $msg)
   {
    global $abversion, $abdate, $boardprog;
    if (!defined('HEADER_PRINTED')) {
        pageheader('Message');
    }
    print "<br>";
    noticemsg($name,$msg);
    pagefooter();
    die();
   }
   
	// Generic closable message
	function infownd($title, $message) {
		global $L;
		static $i = 0;
		
		// First time the javascript is output
		if ($i === 0) {
			$outjs = "
			<script language='javascript'>
				function dismiss(name) {
					document.getElementById(name).style['display'] = 'none';
				}
			</script>";
		} else {
			$outjs = "";
		}
		++$i;
		return "{$outjs}
		<div id='xwnd{$i}' onclick='dismiss(\"xwnd{$i}\")' title='Click to dismiss.'><br>
			$L[TBL1] style='width: 100%'>
				$L[TRh]>$L[TDh]>{$title}<div style='float: right'><a style='cursor: pointer' onclick='dismiss(\"xwnd{$i}\")'>[x]</a></td></tr>
				$L[TR1]>$L[TD1l]>{$message}</td></tr>
			</table>
		<br></div>";
	}
    
  function pagefooter()
   {
    //Used for Affiliates, buttons, links, and navigational tools -Emuz
    global $L, $abversion, $abdate, $boardprog, $misc, $sql, $config;
    //pagestats();
//Injecting SQL self-updater here.
//Updater must run after a page has finished rendering else it generates numerous errors [Epele]
  if(has_perm('no-restrictions') && $config['sqlselfupdate']==true){
    $dir=opendir("../sql");
    $exclusions=array(".","..","old","static","main.sql");
    while($fn = readdir($dir)){
      if(!in_array($fn,$exclusions)){
        $sn=str_split($fn,8);
        if($sn[0]<=$misc['sqlversion']){
          $old=true;
        } else {
          $qlist=file_get_contents("../sql/".$fn);
          $qry=$sql->multi_query($qlist);
          $old=false;
        }
      if($old==false) $boardprog.="<br><b>Sql update applied: $fn ($qry)</b>";
      }
    }
  }
    print "<br>
           $L[TBL2]>$L[TRc]>$L[TD2l]><center><a href=\"https://github.com/acmlmboard/acmlmboard-2\" title=\"Acmlmboard 2\"><img src=\"img/poweredbyacmlm.PNG\"></a><br>
             Acmlmboard v$abversion ($abdate)<br>
             &copy; 2005-".date("Y")." $boardprog
           $L[TBLend]";
    pagestats();
    //miscbar(); disabled until needed. -Emuz
  }

?>
