<?php 
  /**
   * The updater script
   */

  echo "Updating...\n";

  $newVersion = file_get_contents("cv.txt");
  $currentVersion = file_get_contents("../.z_framework");
  echo "Current version: $currentVersion\n";
  echo "New version: $newVersion\n";

  if ($newVersion == $currentVersion) die ("No update needed!");

  copy("default/assets/Z.js", "../assets/js/Z.js");
  file_put_contents("../.z_framework", $currentVersion);

?>