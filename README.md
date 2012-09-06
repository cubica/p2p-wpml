P2P-WPML
========

**p2p-wpml** is a [Wordpress](http://wordpress.org/) plugin that integrates [iCanLocalize's WPML](http://wpml.org/) and [Posts 2 Posts](http://scribu.net/wordpress/posts-to-posts).

This plugin has been only tested with specific versions of P2P, WPML and Wordpress (see the [compatibility matrix in the Wiki](https://github.com/cubica/p2p-wpml/wiki/Version-Compatibility-Matrix)); different versions  may break this plugin's functionality so use it at your own risk.

Features
--------

* **Synchronization of connections between translations**:

	* when a new connection is created between two posts (the origin and the destination), each translation of the origin will be connected to the translation of the destination in the corresponding language (if both exist);
	* when a connection between two posts is deleted, all the connections between the translations of those two posts will be deleted (where they exist);
	* when a new translation of a given post is created, for each connection between the original post and another post (the destination), a new connection is created between the translated post and the translation of the destination post in the corresponding language (if it exists), and metadata from the original connection are copied to the new connection.
	
* **Synchronization of connection metadata between translations**:

	* when metadata are created, updated or deleted on a connection between to posts (the origin and the destination), metadata of connections between the translations of the origin and destination posts are updated accordingly; this includes when a new connection is created on the original posts and its metadata are initialized with default values.
	
* **Only connectable posts in the current language are shown in the P2P metaboxes**:

	* without this plugin, when you edit a post translation (not in the default language), the P2P metaboxes only show connectable posts in the default language.


Installation
------------

1. Extract the downloadable archive inside *wp-content/plugins*, and activate in Wordpress administration.
1. The plugin can be configured accessing the **Posts 2 Posts** link inside the **WPML** settings menu.

**IMPORTANT**: 

* The synchronization feature (both connections and connection metadata) is *DISABLED* by default
* The language filtering feature is *ENABLED* by default

Caveats
-------

* Currently this plugin doesn't manage multiple connections between the same posts (a single connection will be created between the translated posts).
* Currently the synchronization feature is *NOT* retroactive: all connections created before plugin activation will not be synchronized (it can still be done manually as without the plugin)
* This plugin can break P2P cardinality checks, so you shouldn't specify a cardinality when creating connection types.