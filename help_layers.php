<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';
print_header ( '', '', '', true );
echo $helpListStr . '
    <h2>' . $translations['Help'] . ': ' . $translations['Layers'] . '</h2>
    <p>' .
translate ( 'Layers are useful for displaying other users&#39; events in your own calendar. You can specify the user and the color the events will be displayed in.' )
 . '</p>';
$tmp_arr = array (
  translate ( 'Add/Edit/Delete' ) =>
  translate ( 'Clicking the Edit Layers link in the admin section at the bottom of the page will allow you to add/edit/delete layers.' ),
  $translations['Colors'] =>
  translate ( 'The text color of the new layer that will be displayed in your calendar.' ),
  translate ( 'Disabling' ) =>
  translate ( 'Press the Disable Layers link in the admin section at the bottom of the page to turn off layers.' ),
  $translations['Duplicates'] =>
  translate ( 'If checked, events that are duplicates of your events will be shown.' ),
  $translations['Enabling'] =>
  translate ( 'Press the Enable Layers link in the admin section at the bottom of the page to turn on layers.' ),
  $translations['Source'] =>
  translate ( 'Specifies the user that you would like to see displayed in your calendar.' ),
  );
list_help ( $tmp_arr );
if ( $ALLOW_COLOR_CUSTOMIZATION )
  echo '
    <h3>' . $translations['Colors'] . '</h3>
    <p>' . $translations['colors-help'] . '</p>';

echo print_trailer ( false, true, true );

?>
