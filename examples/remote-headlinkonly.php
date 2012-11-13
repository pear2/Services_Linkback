<html>
 <head>
  <title>Target page with head link relation</title>
  <link rel="pingback" href="http://<?php echo $_SERVER['HTTP_HOST'];?>/test-server.php" />
 </head>
 <body>
  <p>
   This page has a link relation in the head, so that
   the pingback client can discover the page's pingback server.
  </p>
 </body>
</html>
