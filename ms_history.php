<?php
include("functions.php");
include("accesscontrol.php");
header1('');
header2(0,"#F0E0FF",1,0);

if (isset($_POST['save_history'])) {
  if (!isset($_POST['confirmed'])) {
    // check for songs already on this event and date
    $sql = "SELECT song.Title, history.UseOrder FROM history LEFT JOIN song".
    " ON history.SongID=song.SongID WHERE EventID=".$_POST['event_id']." AND UseDate='".$_POST['use_date']."' ORDER BY UseOrder";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
    if (mysqli_num_rows($result) > 0) {
      // ask for confirmation before replacing song session
      echo "<table border=0 cellspacing=0 cellpadding=5><tr><td width=350>\n";
      echo "<font color=red><b>There are already songs recorded for this event and date.";
      echo " The list is to the right. If you do not want to replace these songs with your selection,";
      echo " just select your browser's Back button.</b></font>";
      echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>";
      echo "<input type=hidden name=sid_list value=\"$sid_list\">\n";
      echo "<input type=hidden name=event_id value=\"".$_POST['event_id']."\">";
      echo "<input type=hidden name=use_date value=\"".$_POST['use_date']."\">\n";
      echo "<input type=hidden name=confirmed value=\"1\">\n";
      echo "<input type=submit name=save_history value=\"Yes, replace with new selected songs\">\n";
      echo "</form></td><td><b>Previously recorded song session:</b><br>\n";
      while ($row = mysqli_fetch_object($result)) {
        echo "&nbsp; &nbsp; &nbsp;".$row->UseOrder.". ".$row->Title."<br>\n";
      }
      echo "</td></tr></table>\n";
      exit;
    }
  }
  
  echo "<h3 style='color=:#663399'>";
  
  if (isset($_POST['confirmed'])) {   // there are old records that need to be deleted
    $sql = "DELETE FROM history WHERE EventID=".$_POST['event_id']." AND UseDate='".$_POST['use_date']."'";
    if (!$result = mysqli_query($db,$sql)) {
      echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
      exit;
    }
  echo "Old history records deleted.<br>";
  }

  if ($sid_list == "") {
    echo "No new records added, as list was empty.<br>You must have just wanted to get rid of some old data (wink!).";
  } else {
    $sid_array = explode(",",$sid_list);
    $num_sids = count($sid_array);
    for ($i=0; $i<$num_sids; $i++) {
      $sql = "INSERT INTO history (SongID,EventID,UseDate,UseOrder) VALUES (".
      $sid_array[$i].",".$_POST['event_id'].",'".$_POST['use_date']."',".($i+1).")";
      if (!$result = mysqli_query($db,$sql)) {
        echo("<b>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)");
        exit;
      }
    }
    echo $num_sids." new history records added.";
  }
  echo "</h3>";
  exit;
}
?>

<div align="center">
  <h2 style="color:#663399">Choose the event and pick the date:</h2>
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="hform" target="_self" onsubmit="return validate();">
    <input type="hidden" name="sid_list" value="<?php echo $sid_list; ?>">
    <div class="flex-container">
      <div class="flexbox align-left">
        <h3>Event:</h3>
<?php
$result = sqlquery_checked('SELECT * FROM event ORDER BY '.
    (isset($_SESSION['default_event'])?'IF (EventID='.$_SESSION['default_event'].',0,1), ':''). 'Event');
while ($row = mysqli_fetch_object($result)) {
  echo '        <label'.($row->Remarks!==''?' title="'.escape_quotes($row->Remarks).'"':'').
      '><input type="radio" name="event_id" value="'.$row->EventID.'"'.
      ((isset($_SESSION['default_event']) && $row->EventID==$_SESSION['default_event'])?' checked':'').'> '.
      escape_quotes($row->Event)."</label><br>\n";
}
?>
      </div>
      <div class="flexbox">
        <h3><label>Date Used: <input type="text" name="use_date" id="use_date" value="" size="12" maxlength="10"></label></h3>
        <input type="submit" name="save_history" value="Save Data">
      </div>
    </div>
  </form>
</div>
<script>
$( function() {
  $( "#use_date" ).datepicker({ dateFormat: "yy-mm-dd"});
} );

function validate() {
  date = $('#use_date');
  if (date.val() === '') {
    alert('<?=_("You must enter a date.")?>');
    date.click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', date.val()); }
  catch(error) {
    alert('<?=_("Date is invalid.")?>');
    return false;
  }
}
</script>
<?php print_footer(); ?>
