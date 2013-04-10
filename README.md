=== p2p-wpml === 

Integration between WPML and Posts 2 Posts.

== Description ==

**p2p-wpml** is a [WordPress](http://wordpress.org/) plugin that integrates [iCanLocalize's WPML](http://wpml.org/) and [Posts 2 Posts](http://scribu.net/wordpress/posts-to-posts).

The following table shows version compatibility between this plugin, other plugins and WordPress:

* P2P-WPML 1.0

	* P2P 0.8
	* WPML 2.3.3, 2.3.4
	* WordPress 3.2.1

* P2P-WPML 1.1

	* P2P 1.3.1
	* WPML 2.5.2
	* WordPress 3.4.1

* P2P-WPML 1.2.1

	* P2P 1.4.1
	* WPML 2.5.2
	* WordPress 3.4.1

* P2P-WPML 1.2.2

	* P2P 1.4.3
	* WPML 2.6.2
	* WordPress 3.5

* P2P-WPML 1.2.3

	* P2P 1.5.2
	* WPML 2.7.1
	* WordPress 3.5.1
	
This plugin has been only tested with the above version combinations; different versions of WordPress or the plugins may break this plugin's functionality so use it at your own risk.

= Features =


* **Synchronization of connections between translations**:

	* when a new connection is created between two posts (the origin and the destination), each translation of the origin will be connected to the translation of the destination in the corresponding language (if both exist);
	* when a connection between two posts is deleted, all the connections between the translations of those two posts will be deleted (where they exist);
	* when a new translation of a given post is created, for each connection between the original post and another post (the destination), a new connection is created between the translated post and the translation of the destination post in the corresponding language (if it exists), and metadata from the original connection are copied to the new connection.
	
* **Synchronization of connection metadata between translations**:

	* when metadata are created, updated or deleted on a connection between to posts (the origin and the destination), metadata of connections between the translations of the origin and destination posts are updated accordingly; this includes when a new connection is created on the original posts and its metadata are initialized with default values.

* **Only connectable posts in the current language are shown in the P2P metaboxes**:

	* without this plugin, when you edit a post translation (not in the default language), the P2P metaboxes only show connectable posts in the default language.
	
= Caveats =

* Currently this plugin doesn't manage multiple connections between the same posts (a single connection will be created between the translated posts).
* Currently the synchronization feature is NOT retroactive: all connections created before plugin activation will not be synchronized (it can still be done manually as without the plugin).
* This plugin can break P2P cardinality checks, so you shouldn't specify a cardinality when creating connection types.

Links: [**Github**](https://github.com/cubica/p2p-wpml) | [Author's Site](http://www.cubica.eu) | [Twinpictures](http://plugins.twinpictures.de)

== Installation ==

1. Extract the downloadable archive inside *wp-content/plugins*, and activate in WordPress administration.
1. The plugin can be configured accessing the **Posts 2 Posts** link inside the **WPML** settings menu.

**IMPORTANT**: 

* The synchronization feature (both connections and connection metadata) is *DISABLED* by default
* The language filtering feature is *ENABLED* by default

== Frequently Asked Questions == 
= Question? =
Answer 

== Screenshots == 

1. Settings management

== Changelog ==

= 1.2.3 =
* Compatibility with P2P 1.5.2, WPML 2.7.1 and WordPress 3.5.1
* Added ability to synchronize connections on post duplication

= 1.2.2 =
* Compatibility with P2P 1.4.3

= 1.2.1 =
* Fixed notices, see issue https://github.com/cubica/p2p-wpml/issues/7

= 1.2 =
* Compatibility with P2P 1.4.1
* Support for connections between posts and attachments/users
* P2P meta synchronization
* Added warning if WPML and/or P2P are not active
* General refactoring of the synchronizer
* Bug fixing

= 1.1 = 
* Correctly loads the ui.js script for p2p v.1.3.1+
* Compatibility with P2P 1.3.1 and WPML 2.5.2

= 1.0 = 
* First version

== Upgrade Notice ==
= 1.2.3 =
* Compatibility with WordPress 3.5.1
* Compatibility with P2P 1.5.2
* Compatibility with WPML 2.7.1
* Finally: The ability to synchronize connections on post duplication

= 1.2.2 =
* Compatibility with WordPress 3.5
* WPML 2.6.2
* P2P 1.4.3
