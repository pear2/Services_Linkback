<?php include __DIR__ . '/config.php'; ?>
<html>
 <head>
  <title>Pingback test page</title>
 </head>
 <body>
  <p>
   This page contains a link to
   <a href="<?php echo $host; ?>/remote-headeronly.php">remote-headeronly.php</a>
   and is used as client and server test page.
  </p>
  <p>
   It also contains a link to
   <a href="<?php echo $host; ?>/remote-headlinkonly.php">remote-headlinkonly.php</a>
  </p>
 </body>
</html>