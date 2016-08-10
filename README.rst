***********************
PEAR2 Services_Linkback
***********************

Pingback__ and webmention__ client and server implementation for PHP 5.3+.

__ http://hixie.ch/specs/pingback/pingback
__ http://webmention.net/

.. contents::

===============
Linkback server
===============
The package provides a basic pingback+webmention server implementation that can be
customized easily via callbacks.

Usage::

    $srv = new \PEAR2\Services\Linkback\Server();
    $srv->addCallback(new PingbackLogger());
    $srv->run();

Glossary
========

source URL
  Remote URL that links to the local target URL
target URL
  Local URL that gets linked by the source URL


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


===============
Linkback client
===============
Tell someone that you linked to him::

    $from = 'http://my-blog.example.org/somepost.html';
    $to   = 'http://b.example.org/inspiration.html';
    $lbc  = new \PEAR2\Services\Linkback\Client();
    $lbc->send($from, $to);


You can adjust the HTTP_Request2 settings::

    $req = $lbc->getRequest();
    $req->setConfig(
        array(
            'ssl_verify_peer' => false,
            'ssl_verify_host' => false
        )
    );
    $lbc->setRequestTemplate($req);

And change the user agent header sent with the linkback requests::

    $req = $lbc->getRequest();
    $headers = $req->getHeaders();
    $req->setHeader('user-agent', 'my blog engine');
    $lbc->setRequestTemplate($req);

And a debug mode is available, too::

    $lbc->setDebug(true);

This setting stores HTTP responses for later inspection.


============
Installation
============
With PEAR::

    $ pear channel-discover pear2.php.net
    $ pear install pear2/pear2_services_linkback-alpha

Using composer::

    $ composer require pear2/services_linkback


=======================
About Services_Linkback
=======================
Services_Linkback was written by `Christian Weiske <http://cweiske.de/>`_
and is licensed under the
`LGPLv3 or later <https://www.gnu.org/licenses/lgpl-3.0.html>`_.


Homepage
  http://pear2.php.net/PEAR2_Services_Linkback
Bug tracker
  https://github.com/pear2/Services_Linkback/issues
Documentation
  The `examples/`__ folder.
Packagist
  https://packagist.org/packages/pear2/services_linkback

__ https://github.com/pear2/Services_Linkback/tree/master/examples
Unit test status
  https://travis-ci.org/pear2/Services_Linkback

  .. image:: https://travis-ci.org/pear2/Services_Linkback.svg?branch=master
     :target: https://travis-ci.org/pear2/Services_Linkback
