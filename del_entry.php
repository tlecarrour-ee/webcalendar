<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

// First, check to see if this user should be able to delete this event.
if ( $id > 0 ) {
  // first see who has access to edit this entry
  if ( $is_admin ) {
    $can_edit = true;
  } else if ( $readonly ) {
    $can_edit = false;
  } else {
    $can_edit = false;
    $sql = "SELECT webcal_entry.cal_id FROM webcal_entry, " .
      "webcal_entry_user WHERE webcal_entry.cal_id = " .
      "webcal_entry_user.cal_id AND webcal_entry.cal_id = $id " .
      "AND (webcal_entry.cal_create_by = '$login' " .
      "OR webcal_entry_user.cal_login = '$login')";
    $res = dbi_query ( $sql );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row && $row[0] > 0 )
        $can_edit = true;
      dbi_free_result ( $res );
    }
  }
}

// See who owns the event.  Owner should be able to delete.
$res = dbi_query (
  "SELECT cal_create_by FROM webcal_entry WHERE cal_id = $id" );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $owner = $row[0];
  dbi_free_result ( $res );
  if ( $owner == $login ) {
    $my_event = Y;
    $can_edit = true;
  }
}


if ( ! $can_edit ) {
  $error = translate ( "You are not authorized" );
}

if ( $id > 0 && strlen ( $error ) == 0 ) {
  if ( $date != "" ) {
    $thisdate = $date;
  } else {
    $res = dbi_query ( "SELECT cal_date FROM webcal_entry WHERE cal_id = $id" );
    if ( $res ) {
      // date format is 19991231
      $row = dbi_fetch_row ( $res );
      $thisdate = $row[0];
    }
  }

  // Only allow delete of webcal_entry & webcal_entry_repeats
  // if owner or admin, not participant.
  if ( $is_admin || $my_event == "Y") {

    // Email participants that the event was deleted
    $sql = "SELECT cal_firstname, cal_lastname, cal_login , cal_email " .
      "FROM webcal_user, webcal_entry " .
      "WHERE cal_login = webcal_entry.cal_create_by " .
      "AND webcal_entry.cal_id = $id ";
    //echo $sql."<BR>";
    $res = dbi_query ( $sql );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( strlen ( $row[0] ) && strlen ( $row[1] ) )
        $del_name = "$row[0] $row[1]";
      else
        $del_name = $row[2];
      $del_login = $row[2];
      $del_email = $row[3];
      dbi_free_result ( $res );
    }
  
    $sql = "SELECT webcal_entry_user.cal_login, webcal_user.cal_firstname, " .
      "webcal_user.cal_lastname, webcal_user.cal_email " .
      "FROM webcal_entry_user, webcal_user " .
      "WHERE webcal_entry_user.cal_id = $id AND " .
      "webcal_entry_user.cal_login = webcal_user.cal_login ";
    //echo $sql."<BR>";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[0] != $del_login ) {
	  $partlogin[] = $row[0];
          if ( strlen ( $row[1] ) && strlen ( $row[2] ) )
	    $partname[] = "$row[1] $row[2]";
          else
	    $partname[] = $row[0];
	  $partemail[] = $row[3];
        }
      }
      dbi_free_result($res);
    }

    // Get event name
    $sql = "SELECT cal_name FROM webcal_entry WHERE cal_id = $id";
    $res = dbi_query($sql);
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $name = $row[0];
      dbi_free_result ( $res );
    }
  
  
    for ( $i = 0; $i < count ( $partlogin ); $i++ ) {
      $do_send = get_pref_setting ( $participants[$i], "EMAIL_EVENT_DELETED" );
      if ( $do_send == "Y" ) {
        $msg = translate("Hello") . ", " . $partname[$i] . ".\n\n" .
          translate("An appointment has been canceled for you by") .
          " " . $del_name .  ". " .
          translate("The subject was") . " \"" . $name . "\"\n\n";
        if ( strlen ( $del_email ) )
          $extra_hdrs = "From: $del_email\nX-Mailer: " . translate("Title");
        else
          $extra_hdrs = "From: $email_fallback_from\nX-Mailer: " . translate("Title");
        mail ( $partemail[$i],
          translate("Title") . " " . translate("Notification") . ": " . $name,
          $msg, $extra_hdrs );
      }
    }

    dbi_query ( "DELETE FROM webcal_entry WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_site_extras WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_reminder_log WHERE cal_id = $id" );
  } else {
    // not the owner of the event and are not the admin
    // just delete the event from this user's calendar and leave it for
    // everyone else, unless this user is the only participant, in which
    // case, we delete everything about this event.
    $res = dbi_query (
      "SELECT COUNT(cal_login) FROM webcal_entry_user " .
      "WHERE cal_id = $id" );
    $delete_all = FALSE;
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row[0] <= 1 )
        $delete_all = TRUE;
      dbi_free_result ( $res );
    }
    
    if ( $delete_all ) {
      dbi_query ( "DELETE FROM webcal_entry WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_site_extras WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_reminder_log WHERE cal_id = $id" );
    } else {
      dbi_query ( "DELETE FROM webcal_entry_user " .
        "WHERE cal_id = $id AND cal_login = '$login'" );
    }
  }
}

$redir = "";
if ( $thisdate != "" )
  $redir = "?date=$thisdate";
if ( $user != "" ) {
  if ( $redir != "" )
    $redir .= "&";
  $redir .= "user=$user";
}
if ( strlen ( $error ) == 0 ) {
  do_redirect ( "$STARTVIEW.php" . $redir );
  exit;
}
?>
<HTML>
<HEAD><TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></H2></FONT>
<BLOCKQUOTE>
<?php echo $error; ?>
</BLOCKQUOTE>

<?php include "includes/trailer.inc"; ?>

</BODY>
</HTML>
