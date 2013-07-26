<?php 

	class Translation extends CustomPost {
		static $name = "translation" ;
		static $creation_fields = array( 
			'label' => 'translation','description' => 'Translated texts & fragments.',
			'public' => false,'show_ui' => true,'show_in_menu' => true,'capability_type' => 'post', 'map_meta_cap' => true, 
			'hierarchical' => false,'publicly_queryable' => false, 'exclude_from_search' => true,
			'supports' => array('custom-fields', 'title'), 
			'has_archive' => false, 'taxonomies' => array(),
			'labels' => array (
				'name' => 'Translations',
				'singular_name' => 'Translation',
				'menu_name' => 'Translations',
				'add_new' => 'New Language',
				'add_new_item' => 'Add new Language',
				'edit' => 'Update',
				'edit_item' => 'Update Translations',
				'new_item' => 'Register Language',
				'view' => 'View',
				'view_item' => 'View Translations')
		) ;
		static $icon = '\f205';
		static $absent_actions = array('quick-edit');
		static $absent_collumns = array('date');
		
		static $fields = array(
			'code' => array('required' => true, 'label' => 'Code', 'type' => 'text', 'default' => 'en_US', 'description' => "language's IETF code")
		) ;

		static $editable_by = array(
			'configuration' => array('fields' => 'code', 'name' => 'Configuration')
		);

		static $collumns = array(
			'current' => 'Current?',
			'code' => 'IETF Code'
		);

		public function current(){
			if(WPLANG == ''){
				echo $this->code == 'en_US' ? 'Yes (by default)' : 'No' ;
			} else {
				echo WPLANG == $this->code ? 'Yes' : 'No' ;	
			}
			
		}
		public function code(){
			echo $this->code ;
		}

		static $tabs = array(); 
		static $texts = array();

		static function build(){
			$class = get_called_class();
			$namespace = get_namespace($class);
			$base = $namespace . '\Plugin';
			foreach (static::$texts as $tab => $texts) {
				static::$tabs[$tab] = array();
				foreach ($texts as $name => $options) {
					if($tab != 'Main') $name = strtolower($tab)."_$name" ;
					static::$fields[$name] = array(
						'type' => isset($options[1]) && strstr($options[1], '<br/>') ? 'text_area' : 'text',
						'label' => $name,
						'description' => $options[0],
						'html' => array(),
						'size' => 50
					);
					if(isset($options[1])){
						static::$fields[$name]['default'] = $options[1] ;
						static::$fields[$name]['html']['placeholder'] = $options[1] ;
					}
					static::$tabs[$tab][]= $name ;
				}
			}
			add_filter('gettext', function($translated_text, $text, $domain) use($class, $namespace) {
				$app = strtolower($namespace); $translations_var = $app.'_translations' ;
				$text = str_replace('-', '_', $text);
				if(!isset($class::$fields[$text])) return $translated_text ;
				
				global $$translations_var ; 
				if(!isset($$translations_var)){
					$language = WPLANG == '' ? 'en_US' : WPLANG ;
					$$translations_var = get_posts(array('post_type' => $class::$name, 
						'meta_key' => 'code', 'meta_value' => $language
					));
					$$translations_var = new $class($$translations_var[0]);
				}
				

				$translated_text = $$translations_var->$text ;
				if(empty($translated_text)) $translated_text = $class::$fields[$text]['default'] ;
				return $translated_text ;
			}, 20, 3);

			add_action('admin_enqueue_scripts', function() use($base){
				$screen = get_current_screen();
				if(isset($screen->post_type) && 'translation' == $screen->post_type){
					wp_enqueue_script('admin-translation', $base::url('lib/js/utils/translation.js'), array('jquery'));
				}
			});

			parent::build();
		}


	}

 ?>