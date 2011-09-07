P2P-WPML
========

**p2p-wpml** is a [Wordpress](http://wordpress.org/) plugin that integrates [iCanLocalize's WPML](http://wpml.org/) and [Posts 2 Posts](http://scribu.net/wordpress/posts-to-posts).

Features
--------

**Synchronization of connections between translations**:

* when a new connection is created between two posts (the origin and the destination), each translation of the origin will be connected to the translation of the destination in the corresponding language;
* when a connection between two posts is deleted, all the connections between the translations of those two posts will be deleted;
* when a new translation of a given post is created, for each connection between the source post and another post, a new connection is created between the new post and the translation of the destination post in the chosen language.

**Only connectable posts in the current language are shown in the P2P box**.

Installation
------------

Just extract the downloadable archive in a folder named *p2p-wpml* inside *wp-content/plugins*.

Caveats
-------

* Currently this plugin doesn't manage multiple connections between the same posts (a single connection will be created between the translated posts).