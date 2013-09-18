<?php
class P2P_WPML_Synchronizer {
	private static $editedPostIds = array();
	
	private static $inHandler = false;
	
	public static function init() {
		if(P2P_WPML_Admin::shouldSynchronize()) {
			// add p2p hooks
			add_action('p2p_created_connection', array(__CLASS__, 'p2p_insert'));
			add_action('p2p_delete_connections', array(__CLASS__, 'p2p_delete'));
			
			// add a edit_post action for catching post editing
			add_action('edit_post', array(__CLASS__, 'edit_post'), 20, 1);
			
			// add a save_post action for catching post creation
			add_action('save_post', array(__CLASS__, 'save_post'), 20, 2);
			
			// include auto-draft posts in p2p capture queries, so that synchronized connections appear
			// when the translated post is in auto-draft status
			add_action('parse_query', array(__CLASS__, 'change_capture_query_post_status'));
		}
		
		if(P2P_WPML_Admin::shouldSynchronizeMetadata()) {
			// add actions for p2p meta changes
			add_action('added_p2p_meta', array(__CLASS__, 'synchronize_added_metadata'), 20, 4);
			add_action('deleted_p2p_meta', array(__CLASS__, 'synchronize_deleted_metadata'), 20, 4);
			add_action('updated_p2p_meta', array(__CLASS__, 'synchronize_updated_metadata'), 20, 4);
		}
	}
	
	public static function synchronize_added_metadata($metaId, $connectionId, $key, $value) {
		self::exec_safe('synchronize_added_metadata_internal', $metaId, $connectionId, $key, $value);
	}
	
	private static function synchronize_added_metadata_internal($metaId, $connectionId, $key, $value) {
		// get the connection
		$connection = p2p_get_connection($connectionId);
		if(empty($connection)) return;
		
		$translatedConnectionIds = self::get_translated_connection_ids($connection);
		
		foreach($translatedConnectionIds as $translatedConnectionId) {
			p2p_add_meta($translatedConnectionId, $key, $value);
		}
	}
	
	public static function synchronize_deleted_metadata($metaIds, $connectionId, $key, $value) {
		self::exec_safe('synchronize_deleted_metadata_internal', $metaIds, $connectionId, $key, $value);
	}
	
	private static function synchronize_deleted_metadata_internal($metaIds, $connectionId, $key, $value) {
		// get the connection
		$connection = p2p_get_connection($connectionId);
		if(empty($connection)) return;
		
		$translatedConnectionIds = self::get_translated_connection_ids($connection);
		
		foreach($translatedConnectionIds as $translatedConnectionId) {
			p2p_delete_meta($translatedConnectionId, $key, $value);
		}
	}
	
	public static function synchronize_updated_metadata($metaIds, $connectionId, $key, $value) {
		self::exec_safe('synchronize_updated_metadata_internal', $metaIds, $connectionId, $key, $value);
	}
	
	private static function synchronize_updated_metadata_internal($metaIds, $connectionId, $key, $value) {
		// get the connection
		$connection = p2p_get_connection($connectionId);
		if(empty($connection)) return;
	
		$translatedConnectionIds = self::get_translated_connection_ids($connection);
	
		foreach($translatedConnectionIds as $translatedConnectionId) {
			p2p_update_meta($translatedConnectionId, $key, $value);
		}
	}
	
	public static function change_capture_query_post_status($wp_query) {
		if (isset($wp_query->_p2p_capture) || property_exists($wp_query, '_p2p_capture')) {
			$wp_query->set('post_status', array(
				'publish',
				'pending', 
				'draft', 
				'auto-draft', 
				'future', 
				'private', 
				'inherit'
			));
		}
	}
	
	public static function edit_post($postId) {
		self::$editedPostIds[] = $postId;
	}
	
	public static function save_post($postId, $post) {
		self::exec_safe('save_post_internal', $postId, $post);
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
		else {	
			return;
		}
		
		// get the source translation id
		$translationIds = self::get_translation_ids_except_post($trId, $post);
		
		if(isset($translationIds[$sourceLang])) {
			$sourceTranslationId = $translationIds[$sourceLang];
			
			// get connection types
			$connectionTypes = P2P_Connection_Type_Factory::get_all_instances();
			
			foreach($connectionTypes as $connectionTypeName => $connectionType) {
				$connections = array();
				
				$isFromPost = $connectionType->side['from'];
				$isToPost = $connectionType->side['to'];
				
				if($isFromPost) {
					// get the connections originating from the source translation
					$fromConnections = p2p_get_connections($connectionTypeName, array(
						'direction' => 'from',
						'from' => $sourceTranslationId
					));
					
					// for each from connection of the source translation, find the corresponding
					// translation in the current language
					foreach($fromConnections as $fromConnection) {
						$toPostId = $fromConnection->p2p_to;
						$metadata = p2p_get_meta($fromConnection->p2p_id);
						
						//check if the destination post is translated
						if($isToPost && !is_a($isToPost, 'P2P_Side_User') && self::is_post_translated($toPostId)) {
							$toTranslationIds = self::get_post_translation_ids($toPostId);
							if(isset($toTranslationIds[$lang])) $connections[] = array(
								'from' => $postId,
								'to' => $toTranslationIds[$lang],
								'meta' => $metadata
							);
						}
						// otherwise, add the connection anyway
						else {
							$connections[] = array(
								'from' => $postId,
								'to' => $toPostId,
								'meta' => $metadata
							);
						}
					}
				}
				
				if($isToPost) {
					// get the connections pointing to the source translation
					$toConnections = p2p_get_connections($connectionTypeName, array(
						'direction' => 'from',
						'to' => $sourceTranslationId
					));
											
					// for each to connection of the source translation, find the corresponding
					// translation in the current language
					foreach($toConnections as $toConnection) {
						$fromPostId = $toConnection->p2p_from;
						$metadata = p2p_get_meta($fromConnection->p2p_id);
						
						//check if the origin post is translated
						if($isFromPost && !is_a($isFromPost, 'P2P_Side_User') && self::is_post_translated($fromPostId)) {
							$fromTranslationIds = self::get_post_translation_ids($fromPostId);
							if(isset($fromTranslationIds[$lang])) $connections[] = array(
								'from' => $fromTranslationIds[$lang],
								'to' => $postId,
								'meta' => $metadata
							);
						}
						// otherwise, add the connection anyway
						else {
							$connections[] = array(
								'from' => $fromPostId,
								'to' => $postId,
								'meta' => $metadata
							);
						}
					}
				}
				
				if(!empty($connections)) {
					self::create_connections($connectionTypeName, $connections);
				}
			}
		}
	}
	
	public static function p2p_insert($connectionId) {
		self::exec_safe('p2p_insert_internal', $connectionId);
	}
	
	private static function p2p_insert_internal($connectionId) {
		$connection = p2p_get_connection($connectionId);
		if(empty($connection)) return;
		
		$tuples = self::get_translated_tuples($connection);
		
		self::create_connections($connection->p2p_type, $tuples, p2p_get_meta( $connectionId ));
	}
	
	public static function p2p_delete($connectionIds) {
		self::exec_safe('p2p_delete_internal', $connectionIds);
	}
	
	private static function p2p_delete_internal($connectionIds) {
		foreach($connectionIds as $connectionId) {
			$connection = p2p_get_connection($connectionId);
			if(empty($connection)) return;
			
			$tuples = self::get_translated_tuples($connection);
			
			self::delete_connections($connection->p2p_type, $tuples);
		}
	}
	
	private static function get_translated_connection_ids(&$connection) {
		$tuples = self::get_translated_tuples($connection);
		
		$connectionIds = array();
		foreach($tuples as $tuple) {
			$connectionIds += p2p_get_connections($connection->p2p_type, array(
				'direction' => 'from',
				'from' => $tuple['from'],
				'to' => $tuple['to'],
				'fields' => 'p2p_id'
			));
		}
		
		return array_unique($connectionIds);
	}
	
	private static function get_translated_tuples(&$connection) {
		$tuples = array();
		
		$typeObj = p2p_type($connection->p2p_type);
		$isFromPost = $typeObj->side['from'];
		$isToPost = $typeObj->side['to'];
		$isFromTranslated = $isFromPost && !is_a($isFromPost, 'P2P_Side_User') && self::is_post_translated($connection->p2p_from);
		$isToTranslated = $isToPost && !is_a($isToPost, 'P2P_Side_User') && self::is_post_translated($connection->p2p_to);
		
		if($isFromTranslated) {
			$fromTranslationIds = self::get_post_translation_ids($connection->p2p_from);
			if($isToTranslated) {
				$toTranslationIds = self::get_post_translation_ids($connection->p2p_to);
				foreach($fromTranslationIds as $lang => $fromTranslationId) {
					if(isset($toTranslationIds[$lang])) {
						$tuples[] = array(
							'from' => $fromTranslationId,
							'to' => $toTranslationIds[$lang]
						);
					}
				}
			}
			else {
				foreach($fromTranslationIds as $fromTranslationId) {
					$tuples[] = array(
						'from' => $fromTranslationId,
						'to' => $connection->p2p_to
					);
				}
			}
		}
		else if($isToTranslated) {
			$toTranslationIds = self::get_post_translation_ids($connection->p2p_to);
			foreach($toTranslationIds as $toTranslationId) {
				$tuples[] = array(
					'from' => $connection->p2p_from,
					'to' => $toTranslationId
				);
			}
		}
		
		return $tuples;
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
	
	private static function create_connections($type, $tuples, $metas=null) {
		
		foreach($tuples as $tuple) {
			// check that the connection is unique
			$args = array(
				'direction' => 'from',
				'from' => $tuple['from'],
				'to' => $tuple['to']
			);
			
			if(!p2p_connection_exists($type, $args)) {
				$connectionId = p2p_create_connection($type, $args);
				
				$_metas = !empty($tuple['meta']) ? $tuple['meta'] : $metas;
				
				if(!empty($_metas)) {
					foreach($_metas as $key => $values) {
						foreach($values as $value) {
							p2p_add_meta($connectionId, $key, $value);
						}
					}
				}
			}
		}
	}
	
	private static function delete_connections($type, $tuples) {
		foreach($tuples as $tuple) {
			p2p_delete_connections($type, array(
				'direction' => 'from',
				'from' => $tuple['from'],
				'to' => $tuple['to']
			));
		}
	}
	
	private static function is_post_translated($postId) {
		global $sitepress;
		
		$postType = get_post_type($postId);
		if($postType !== false) return $sitepress->is_translated_post_type($postType);
		return false;
	}
	
	private static function exec_safe() {
		if(self::$inHandler === true) return null;
		self::$inHandler = true;
		
		$args = func_get_args();
		$function = array_shift($args);
		$result = call_user_func_array(array(__CLASS__, $function), $args);
		
		self::$inHandler = false;
		return $result;
	}
}
