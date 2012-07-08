<?php
class P2P_WPML_Synchronizer {
	private static $editedPostIds = array();

	public static function init() {
		if(P2P_WPML_Admin::shouldSynchronize()) {
			add_action('p2p_created_connection', array(__CLASS__, 'p2p_insert'));
			add_action('p2p_delete_connections', array(__CLASS__, 'p2p_delete'));

			// add a edit_post action for catching post editing
			add_action('edit_post', array(__CLASS__, 'edit_post'), 20, 1);

			// add a save_post action for catching post creation
			add_action('save_post', array(__CLASS__, 'save_post'), 20, 2);
		}
	}

	public static function edit_post($postId) {
		self::$editedPostIds[] = $postId;
	}

	public static function save_post($postId, $post) {
		if(self::$inHandler === true) return;
		self::$inHandler = true;

		self::save_post_internal($postId, $post);

		self::$inHandler = false;
	}

	private static function save_post_internal($postId, $post) {
		global $sitepress, $wpdb;

		// make sure that this is not a revision
		if(wp_is_post_revision($postId) !== false) return;

		// make sure that post has been newly created
		if(in_array($postId, self::$editedPostIds)) return;

		// make sure we have required translation data
		$elementType = 'post_' . $post->post_type;
		// check get parameters
		if(isset($_GET['trid']) && isset($_GET['source_lang']) && isset($_GET['lang'])) {
			$trId = $_GET['trid'];
			$lang = $_GET['lang'];
			$sourceLang = $_GET['source_lang'];
		}
		// check post parameters
		else if(isset($_POST['icl_trid']) && isset($_POST['icl_post_language'])) {
			$trId = $_POST['icl_trid'];
			$lang = $_POST['icl_post_language'];
			$sourceLang = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE trid={$trId} AND source_language_code IS NULL");
		}
		// check translations
		else if($languageDetails = $sitepress->get_element_language_details($postId, $elementType)) {
			$trId = $languageDetails->trid;
			$lang = $languageDetails->language_code;
			$sourceLang = $languageDetails->source_language_code;
		}
		else return;

		// get the source translation id
		$translationIds = self::get_translation_ids_except_post($trId, $post);

		if(isset($translationIds[$sourceLang])) {
			$sourceTranslationId = $translationIds[$sourceLang];

			// get connection types
			$types = self::get_connection_types();

			foreach($types as $type) {
				$connections = array();

				// get the connections originating from the source translation
				$fromConnections = p2p_get_connections($type, array(
					'direction' => 'from',
					'from' => $sourceTranslationId
				));

				// for each from connection of the source translation, find the corresponding
				// translation in the current language
				foreach($fromConnections as $fromConnection) {
					$toPostId = $fromConnection->p2p_to;

					//check if the destination post is translated
					if(self::is_post_translated($toPostId)) {
						$toTranslationIds = self::get_post_translation_ids($toPostId);
						if(isset($toTranslationIds[$lang])) $connections[] = array(
							'from' => $postId,
							'to' => $toTranslationIds[$lang]
						);
					}
					// otherwise, add the connection anyway
					else {
						$connections[] = array(
							'from' => $postId,
							'to' => $toPostId
						);
					}
				}

				// get the connections pointing to the source translation
				$toConnections = p2p_get_connections($type, array(
					'direction' => 'to',
					'to' => $sourceTranslationId
				));

				// for each to connection of the source translation, find the corresponding
				// translation in the current language
				foreach($toConnections as $toConnection) {
					$fromPostId = $toConnection->p2p_from;

					//check if the origin post is translated
					if(self::is_post_translated($fromPostId)) {
						$fromTranslationIds = self::get_post_translation_ids($fromPostId);
						if(isset($fromTranslationIds[$lang])) $connections[] = array(
							'from' => $fromTranslationIds[$lang],
							'to' => $postId
						);
					}
					// otherwise, add the connection anyway
					else {
						$connections[] = array(
							'from' => $fromPostId,
							'to' => $postId
						);
					}
				}

				if(!empty($connections)) self::create_connections($type, $connections);
			}
		}
	}

	static function p2p_insert($p2p_id) {
		$connection = p2p_get_connection($p2p_id);
		$type = $connection->p2p_type;
		$fromId = $connection->p2p_from;
		$toId = $connection->p2p_to;

		$isFromTranslated = self::is_post_translated($fromId);
		$isToTranslated = self::is_post_translated($toId);

		$connections = array();

		if($isFromTranslated) {
			$fromTranslationIds = self::get_post_translation_ids($fromId);
			if($isToTranslated) {
				$toTranslationIds = self::get_post_translation_ids($toId);
				foreach($fromTranslationIds as $lang => $fromTranslationId) {
					if(isset($toTranslationIds[$lang])) {
						$connections[] = array(
							'from' => $fromTranslationId,
							'to' => $toTranslationIds[$lang]
						);
					}
				}
			}
			else {
				foreach($fromTranslationIds as $fromTranslationId) {
					$connections[] = array(
						'from' => $fromTranslationId,
						'to' => $toId
					);
				}
			}
		}
		else if($isToTranslated) {
			$toTranslationIds = self::get_post_translation_ids($toId);
			foreach($toTranslationIds as $toTranslationId) {
				$connections[] = array(
					'from' => $fromId,
					'to' => $toTranslationId
				);
			}
		}

		self::create_connections($type, $connections);
	}

	static function p2p_delete($connectionIds) {
		foreach($connectionIds as $connectionId) {
			$connection = self::get_connection_data($connectionId);
			if(empty($connection)) continue;

			$deletableConnections = array();

			$isFromTranslated = self::is_post_translated($connection['from']);
			$isToTranslated = self::is_post_translated($connection['to']);
			$args = array(
				'direction' => 'from',
				'from' => $connection['from'],
				'to' => $connection['to']
			);

			if($isFromTranslated) {
				$fromTranslationIds = self::get_post_translation_ids($connection['from']);

				if($isToTranslated) {
					$toTranslationIds = self::get_post_translation_ids($connection['to']);

					foreach($fromTranslationIds as $lang => $fromTranslationId) {
						if(isset($toTranslationIds[$lang])) $deletableConnections[] = array(
							'from' => $fromTranslationId,
							'to' => $toTranslationIds[$lang]
						);
					}
				}
				else {
					foreach($fromTranslationIds as $fromTranslationId) {
						$deletableConnections[] = array(
							'from' => $fromTranslationId,
							'to' => $connection['to']
						);
					}
				}
			}
			else if($isToTranslated) {
				$toTranslationIds = self::get_post_translation_ids($connection['to']);

				foreach($toTranslationIds as $toTranslationId) {
					$deletableConnections[] = array(
						'from' => $connection['from'],
						'to' => $toTranslationId
					);
				}
			}

			if(!empty($deletableConnections)) self::delete_connections($connection['type'], $deletableConnections);
		}
	}

	private static function get_post_translation_ids($postId) {
		global $sitepress;

		$post = get_post($postId);
		$postType = $post->post_type;
		// get the trid
		$trId = $sitepress->get_element_trid($postId, 'post_' . $postType);

		return self::get_translation_ids_except_post($trId, $post);
	}

	private static function get_translation_ids_except_post($trId, &$post) {
		global $sitepress;

		$translationIds = array();

		if(!empty($trId)) {
			// get the translations
			$translations = $sitepress->get_element_translations($trId, 'post_' . $post->post_type);
			foreach($translations as $lang => $translation) {
				if($translation->element_id != $post->ID) $translationIds[$lang] = $translation->element_id;
			}
		}

		return $translationIds;
	}

	private static function get_connection_types() {
		$types = P2P_Connection_Type_Factory::get_all_instances();
		return array_keys($types);
	}

	private static function create_connections($type, $connections) {
		foreach($connections as $connection) {
			$fromId = $connection['from'];
			$toId = $connection['to'];
			// check that the connection is unique
			$args = array(
				'direction' => 'from',
				'from' => $fromId,
				'to' => $toId
			);

			if(!p2p_connection_exists($type, $args)) p2p_create_connection($type, $args);
		}
	}

	private static function delete_connections($type, $connections) {
		foreach($connections as $connection) {
			p2p_delete_connections($type, array(
				'direction' => 'from',
				'from' => $connection['from'],
				'to' => $connection['to']
			));
		}
	}

	private static function get_connection_data($connectionId) {
		global $wpdb;

		// TODO: use p2p_get_connection()
		$sql = "SELECT p2p_type, p2p_from, p2p_to FROM `" . $wpdb->p2p . "` WHERE p2p_id='" . $wpdb->escape($connectionId) . "'";
		$connection = $wpdb->get_row($sql);
		if(empty($connection)) return null;
		return array(
			'from' => $connection->p2p_from,
			'to' => $connection->p2p_to,
			'type' => $connection->p2p_type
		);
	}

	private static function is_post_translated($postId) {
		global $sitepress;

		$postType = get_post_type($postId);
		if($postType !== false) return $sitepress->is_translated_post_type($postType);
		return false;
	}
}
