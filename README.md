P2P-WPML
========

**p2p-wpml** is a [Wordpress](http://wordpress.org/) plugin that integrates [iCanLocalize's WPML](http://wpml.org/) and [Posts 2 Posts](http://scribu.net/wordpress/posts-to-posts).

The following table shows version compatibility between this plugin, other plugins and Wordpress:

<table>
	<tr>
		<th>P2P-WPML version</th>
		<th>P2P version</th>
		<th>WPML version</th>
		<th>Wordpress version</th>
	</tr>
	<tr>
		<td><strong>1.0</strong></td>
		<td>0.8</td>
		<td>2.3.3<br />
		2.3.4</td>
		<td>3.2.1</td>
	</tr>
	<tr>
		<td><strong>1.1</strong></td>
		<td>1.3.1</td>
		<td>2.5.2</td>
		<td>3.4.1</td>
	</tr>
</table>

This plugin has been only tested with the above version combinations; different versions of Wordpress or the plugins may break this plugin's functionality so use it at your own risk.

Features
--------

* **Synchronization of connections between translations**:

	* when a new connection is created between two posts (the origin and the destination), each translation of the origin will be connected to the translation of the destination in the corresponding language (if both exist);
	* when a connection between two posts is deleted, all the connections between the translations of those two posts will be deleted (where they exist);
	* when a new translation of a given post is created, for each connection between the original post and another post (the destination), a new connection is created between the translated post and the translation of the destination post in the corresponding language (if it exists).
	
* **Only connectable posts in the current language are shown in the P2P metaboxes**:

	* without this plugin, when you edit a post translation (not in the default language), the P2P metaboxes only show connectable posts in the default language.


Installation
------------

1. Extract the downloadable archive inside *wp-content/plugins*, and activate in Wordpress administration.
1. The plugin can be configured accessing the **Posts 2 Posts** link inside the **WPML** settings menu.

**IMPORTANT**: 

* The synchronization feature is *DISABLED* by default
* The language filtering feature is *ENABLED* by default

Caveats
-------

* Currently this plugin doesn't manage multiple connections between the same posts (a single connection will be created between the translated posts).
* Currently the synchronization feature is *NOT* retroactive: all connections created before plugin activation will not be synchronized (it can still be done manually as without the plugin)