<?php
//Uses editpost.php as template

  require 'lib/common.php';

  if($act=$_POST[action])
  {
    $pid=$_POST[pid];  

  }
  else
  {
    $pid=$_GET[pid];
  }
  
  checknumeric($pid);

  needs_login(1);

  $thread=$sql->fetchq('SELECT p.user puser, t.*, f.title ftitle, f.private fprivate, f.readonly freadonly '
                      .'FROM posts p '
                      .'LEFT JOIN threads t ON t.id=p.thread '
                      .'LEFT JOIN forums f ON f.id=t.forum '
                      ."WHERE p.id=$pid AND t.announce=1 AND t.forum IN ".forums_with_view_perm());


  if (!$thread) $pid = 0;
if($loguser[redirtype]==0 || $act!="Submit"){ //Classical Redirect
  pageheader('Edit announcement title',$thread[forum]);
  echo "<script language=\"javascript\" type=\"text/javascript\" src=\"tools.js\"></script>";
}
else if (!can_edit_post(array('user'=>$thread['puser'], 'tforum' => $thread['forum']))) {
      $err=
          print
        "$L[TBL1]>
".      "  $L[TR2]>
".      "    $L[TD1c]>
".      "      <a href=http://board.kafuka.org/profile.php?id=156><span class=nc12 style=color:#F185C9;>SquidEmpress</span></a> decided to put this message here because she felt like it.<br> <a href=./>Back to main</a> 
".      "$L[TBLend]
";
      pagefooter();
      die();
  }
  elseif($pid==-1){
      $err=
          print
        "$L[TBL1]>
".      "  $L[TR2]>
".      "    $L[TD1c]>
".      "      <a href=http://board.kafuka.org/profile.php?id=156><span class=nc12 style=color:#F185C9;>SquidEmpress</span></a> decided to put this message here because she felt like it.<br> <a href=./>Back to main</a>
".      "$L[TBLend]
";
      pagefooter();
      die();
  }

  $top='<a href=./>Main</a> '
    ."- <a href=forum.php?id=$thread[forum]>$thread[ftitle]</a> "
    .'- Edit announcement title';

  $res=$sql->query  ("SELECT u.id, p.user, p.mood, p.nolayout, pt.text "
                    ."FROM posts p "
                    ."LEFT JOIN poststext pt ON p.id=pt.id "
                    ."JOIN ("
                      ."SELECT id,MAX(revision) toprev FROM poststext GROUP BY id"
                    .") as pt2 ON pt2.id=pt.id AND pt2.toprev=pt.revision "
                    ."LEFT JOIN users u ON p.user=u.id "
                    ."WHERE p.id=$pid");

  if(@$sql->numrows($res)<1){
    $err=
          print
        "$L[TBL1]>
".      "  $L[TR2]>
".      "    $L[TD1c]>
".      "      <a href=http://board.kafuka.org/profile.php?id=156><span class=nc12 style=color:#F185C9;>SquidEmpress</span></a> decided to put this message here because she felt like it.<br> <a href=./>Back to main</a>
".      "$L[TBLend]
";
      pagefooter();
      die();
    }


  $post=$sql->fetch($res);
if($err){
//if($loguser[redirtype]==1 && $act=="Submit"){ pageheader('Edit announcement title',$thread[forum]); }
    print "$top - Error
".        "<br><br>
".        "$L[TBL1]>
".        "  $L[TD1c]>
".        "$err
".        "$L[TBLend]
";
  }elseif(!$act){
    print "$top
".        "<br><br>
".        "$L[TBL1]>
".        " <form action=editannouncetitle.php method=post>
".        "  $L[TRh]>
".        "    $L[TDh] colspan=2>Edit Announcement Title</td>
".        "  $L[TR]>
".        "    $L[TD1c]>Title:</td>
".        "    $L[TD2]>$L[INPt]=title size=100 maxlength=100 value='".$thread[title]."' class='right'></td>
".        "  $L[TR1]>
".        "    $L[TD]>&nbsp;</td>
".        "    $L[TD]>
".        "      $L[INPh]=pid value=$pid>
".        "      $L[INPs]=action value=Submit>
".        "    </td>
".        " </form>
".        "$L[TBLend]
";
  }elseif($act=='Submit'){
    $sql->query("UPDATE threads SET title='$_POST[title]' WHERE id='$thread[id]'");

if($loguser[redirtype]==0){ //Classical Redirect
    print "$top - Submit
".        "<br><br>
".        "$L[TBL1]>
".        "  $L[TD1c]>
".        "    Announcement title edited!<br>
".        "    ".redirect("thread.php?pid=$pid#$pid",htmlval($thread[title]))."
".        "$L[TBLend]
";
} else { //Modern redirect
  redir2("thread.php?pid=$pid#edit","-1");
}
  }

  pagefooter();
?>