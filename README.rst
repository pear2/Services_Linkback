***********************
PEAR2 Services_Linkback
***********************

Pingback and webmention client and server implementation for PHP 5.3+.


Glossary:

- source URL: Remote URL that links to the local target URL
- target URL: Local URL that gets linked by the source URL


===============
Linkback server
===============
The package provides a basic pingback+webmention server implementation that can be
customized easily via callbacks.

Usage::

    $srv = new \PEAR2\Services\Linkback\Server();
    $srv->addCallback(new PingbackLogger());
    $srv->run();


Customization via callbacks
===========================
The server provides 4 types of callbacks to modify its behaviour.
Each callback needs to implement one of the four interfaces:


Services\\Linkback\\Server\\Callback\\ITarget
---------------------------------------------
Verifies that the target URL exists in the local system.
Useful to filter out pingbacks for non-existant URLs.

FIXME: Default implementation


Services\\Linkback\\Server\\Callback\\ISource
---------------------------------------------
Fetches the source URL for further verification.
Used to determine if the source URL really exists.

Services_Pingback provides the ``Services\Linkback\Server\Callback\FetchSource``
callback class that is automatically registered with the server.


Services\\Linkback\\Server\\Callback\\ILink
-------------------------------------------
Verifies that the source URL content really links to the target URL.
Used to filter out fake pingbacks that do not actually provide links.

Services_Pingback provides the ``Services\Linkback\Server\Callback\LinkExists``
callback class that is automatically registered with the server.


Services\\Linkback\\Server\\Callback\\IStorage
----------------------------------------------
After all verifications have been done, the storage finally handles
the pingback - it could e.g. log it to a file or a database.

Services_Pingback does not provide a default storage implementation; you have
to write it yourself.



TODO
----
See what we can learn from
http://www.acunetix.com/blog/web-security-zone/wordpress-pingback-vulnerability/


============
Installation
============
::

    $ pear channel-discover pear2.php.net
    $ pear install pear2/pear2_services_linkback-alpha
