<?php
	
	class RestPost extends CustomPost {
		static $uid_is = 'post_name';

		public function __get($field){
			if(isset(static::$fields[$field]) && isset(static::$fields[$field]['is'])){
				$field = static::$fields[$field]['is'];
			}
			return parent::__get($field);
		}

		public function json_values(){
			$array = array();
			foreach(static::$fields as $field => $options){
				$array[$field] = isset($options['is']) ? $this->$options['is'] : $this->$field ;
			}
			return $array; 
		}

		static function headers(){
			return array();
		}

		public function uid(){
			$attr = static::$uid_is ; 
			return $this->$attr;
		}

		static function make_request($method, $url, $params){
			$pest = new \PestJSON (static::endpoint());
			return $pest->$method($url, $params, static::headers());
		}

		static function build(){
			$class = get_called_class(); $namespace = get_namespace($class);
			parent::build();
			add_action(sprintf("%s-%s-save", 
				strtolower($namespace), $class::$name), 
			function($id, $data) use($class) {
				$object = new $class($id);
				if($data['_new']){
					$class::make_request('post', '/', $object->json_values());
				} else {
					$class::make_request('put', '/'.$object->uid(), $object->json_values());
				}
			}, 10, 2);

		}
	}


?>