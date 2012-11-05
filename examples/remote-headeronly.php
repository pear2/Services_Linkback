<?php
header('X-Pingback: http://' . $_SERVER['HTTP_HOST'] . '/test-server.php');
?>
<html>
 <head>
  <title>Pingback test page</title>
 </head>
 <body>
  <p>
   This page sends only a X-Pingback header, and does not contain a
   pingback link relation.
  </p>
 </body>
</html>