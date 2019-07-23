<?php 
  /**
   * The updater script
   */

  $currentVersion = file_get_contents("cv.txt");

  copy("default/assets/Z.js", "../assets/js/Z.js");
  file_put_contents("../.z_framework", $currentVersion);

?>