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
				
				$isFromPost = $connectionType->object['from'] == 'post';
				$isToPost = $connectionType->object['to'] == 'post';
				
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
						
						//check if the destination post is translated
						if($isToPost && self::is_post_translated($toPostId)) {
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
						
						//check if the origin post is translated
						if($isFromPost && self::is_post_translated($fromPostId)) {
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
				}
				
				if(!empty($connections)) {
					self::create_connections($connectionTypeName, $connections);
				}
			}
		}
	}
	
	public static function p2p_insert($connectionId) {
		if(self::$inHandler === true) return;
		self::$inHandler = true;
		
		try {
			$connection = p2p_get_connection($connectionId);
			if(empty($connection)) throw new Exception('Cannot find connection with id ' . $connectionId);
			
			$typeObj = P2P_Connection_Type_Factory::get_instance($connection->p2p_type);
			$isFromPost = $typeObj->object['from'] == 'post';
			$isToPost = $typeObj->object['to'] == 'post';
	
			$isFromTranslated = $isFromPost && self::is_post_translated($connection->p2p_from);
			$isToTranslated = $isToPost && self::is_post_translated($connection->p2p_to);
	
			$connections = array();
			
			if($isFromTranslated) {
				$fromTranslationIds = self::get_post_translation_ids($connection->p2p_from);
				if($isToTranslated) {
					$toTranslationIds = self::get_post_translation_ids($connection->p2p_to);
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
							'to' => $connection->p2p_to
						);
					}
				}
			}
			else if($isToTranslated) {
				$toTranslationIds = self::get_post_translation_ids($connection->p2p_to);
				foreach($toTranslationIds as $toTranslationId) {
					$connections[] = array(
						'from' => $connection->p2p_from,
						'to' => $toTranslationId
					);
				}
			}
			
			self::create_connections($connection->p2p_type, $connections);
		}
		catch(Exception $ex) {
			if(WP_DEBUG) trigger_error($ex->__toString());
		}
		
		self::$inHandler = false;
	}
	
	public static function p2p_delete($connectionIds) {
		if(self::$inHandler === true) return;
		self::$inHandler = true;
		
		try {
			foreach($connectionIds as $connectionId) {
				$connection = p2p_get_connection($connectionId);
				if(empty($connection)) throw new Exception('cannot find connection with id ' . $connectionId);
				
				$deletableConnections = array();
				
				$typeObj = P2P_Connection_Type_Factory::get_instance($connection->p2p_type);
				$isFromPost = $typeObj->object['from'] == 'post';
				$isToPost = $typeObj->object['to'] == 'post';
				$isFromTranslated = $isFromPost && self::is_post_translated($connection->p2p_from);
				$isToTranslated = $isToPost && self::is_post_translated($connection->p2p_to);
				
				$args = array(
					'direction' => 'from',
					'from' => $connection->p2p_from,
					'to' => $connection->p2p_to	
				);
				
				if($isFromTranslated) {
					$fromTranslationIds = self::get_post_translation_ids($connection->p2p_from);
					
					if($isToTranslated) {
						$toTranslationIds = self::get_post_translation_ids($connection->p2p_to);
							
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
								'to' => $connection->p2p_to
							);
						}	
					}
				}
				else if($isToTranslated) {
					$toTranslationIds = self::get_post_translation_ids($connection->p2p_to);
					
					foreach($toTranslationIds as $toTranslationId) {
						$deletableConnections[] = array(
							'from' => $connection->p2p_from,
							'to' => $toTranslationId	
						);
					}
				}
				
				if(!empty($deletableConnections)) self::delete_connections($connection->p2p_type, $deletableConnections);
			}
		}
		catch(Exception $ex) {
			if(WP_DEBUG) trigger_error($ex->__toString());
		}
		
		self::$inHandler = false;
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
	
	private static function is_post_translated($postId) {
		global $sitepress;
		
		$postType = get_post_type($postId);
		if($postType !== false) return $sitepress->is_translated_post_type($postType);
		return false;
	}
}