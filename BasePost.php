<?php 
	class BasePost extends BaseItem {

		static $fields = array() ;
		static $taxonomies = array();
		public $unfiltered_fields = array();
		static $meta_type = 'post';
	
		function __set($name, $value){
			if('content' == $name){
				wp_update_post( array('ID' => $this->ID, 'post_content' => $value) );
			} elseif('title' == $name) {
				wp_update_post( array('ID' => $this->ID, 'post_title' => $value) );
			}else {
				parent::__set($name, $value);
			}
		}
	}	
?>