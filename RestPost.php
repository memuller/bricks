<?php
	
	class RestPost extends CustomPost {
		static $uid_is = 'ID';
		static $endpoint;

		public function __get($field){
			if(isset(static::$fields[$field]) && isset(static::$fields[$field]['is'])){
				$field = static::$fields[$field]['is'];
			}
			return parent::__get($field);
		}

		public function json_values(){
			$array = array();
			foreach(static::$fields as $field => $options){
				if(strpos($field, "-") !== false) continue;
				if(isset($options['local']) && $options['local']) continue; 
				$array[$field] = isset($options['is']) ? $this->$options['is'] : $this->$field ;
			}
			return $array; 
		}

		static function headers(){
			return array();
		}

		static function local_uid_field(){
			$field = static::$uid_is ;
			if(isset(static::$fields[$field]['is'])){
				return static::$fields[$field]['is'];
			} else {
				return $field;
			}
		}

		public function uid(){
			$attr = static::$uid_is ;
			if(isset(static::$fields[$uid]['is'])){
				$attr = static::$fields[$uid]['is'];
			}
			return $this->$attr;
		}

		static function resource_name(){
			if(isset(static::$plural_name)){
				return static::$plural_name;
			} else {
				return static::$name .'s';
			}
		}

		static function endpoint(){
			return static::$endpoint;
		}

		public function resource_url(){
			return get_post_meta($this->ID, '_resource_url', true);
		}

		static function make_request($method, $url, $params=[]){
			$pest = new \PestJSON (static::endpoint());
			return $pest->$method($url, $params, static::headers());
		}

		public function set_from_values($values){
			foreach ($values as $key => $value) {
				if(!isset(static::$fields[$key])) continue;
				
			}
		}

		static function map_to_local($data){
			$actual_data = [];
			$local_fields = static::$fields;
			foreach($local_fields as $field => $options){
				if(strpos($field, '-') !== false){
					$broken_field = explode('-', $field);
					if(isset($data[$broken_field[0]])){
						$array = &$data[$broken_field[0]];
						if(isset($array[$broken_field[1]])){
							$data[$field] = $array[$broken_field[1]];
							unset($array[$broken_field[1]]);
						}
					}
				}
			}

			foreach ($data as $field => $value) {
				if(!isset(static::$fields[$field])) continue;
				if(isset(static::$fields[$field]['is'])){
					if(static::$fields[$field]['is'] == 'ID') continue;
					$field = static::$fields[$field]['is'];
				}

				unset($local_fields[$field]);

				$actual_data[$field] = $value; 
			}
			return $actual_data;
		} 

		public function fetch(){
			$response = static::make_request('get', $this->resource_url(), static::headers());
			$data = static::map_to_local($response);
			foreach ($data as $field => $value) {
				$this->$field = $value; 
			}
		}

		static function fetch_all($original_parameters = []){
			$offset = 0; $items = []; $uid_field = static::$uid_is;
			do {
				$params = array_merge(array('offset' => $offset), $original_parameters);
				$response = static::make_request('get', static::endpoint(), $params);
				$fetched = $response[static::resource_name()];
				$items = array_merge($items, $fetched);
				$offset += 100;
			} while (!empty($fetched));
			
			foreach ($items as $item) {
				$url = static::endpoint().'/'.$item[$uid_field];
				static::create_or_update($url);
			}
		}

		static function create_or_update($url){
			$response = static::make_request('get', $url);
			$uid_field = static::$uid_is;
			$uid = $response[$uid_field];
			$resource_url = static::endpoint().'/'.$uid;


			$posts = get_posts([
				'post_status' => 'publish',
				'post_type' => static::$name,
				'meta_key' => '_resource_url',
				'meta_value' => $resource_url
			]);

			$post = empty($posts) ? null : $posts[0];

			if(!$post){
				$post = static::create([]);
				update_post_meta($post->ID, '_resource_url', $resource_url);	
			}
			$post = new static($post->ID);
			$post->fetch();
		}

		public function destroy(){

		}

		public function push(){
			if($this->_resource_url){
				static::make_request('put', '/'.$this->uid(), $this->json_values());
			} else {
				static::make_request('post', '/', $this->json_values());
				update_post_meta($this->ID, '_resource_url', static::endpoint().'/'.$this->uid());
			}
		}

		public function action($method, $action, $params=[]){
			static::make_request($method, $this->resource_url().'/'.$action, $params);
		}

		static function build(){
			$class = get_called_class(); $namespace = get_namespace($class);
			parent::build();
			add_action(sprintf("%s-%s-save", 
				strtolower($namespace), $class::$name), 
			function($id, $data) use($class) {
				$object = new $class($id);
				$object->push();
			}, 10, 2);

			add_action('before_delete_post', function($id){
				$post = new static($id);
				$post->destroy();
			});
		}

		static function by_uid($uid){
			if(static::$uid_is == 'ID'){
				$post = get_post($uid);
			} else {
				$posts = static::all([$uid_is => $uid]);
				$post = empty($posts) ? null : $posts[0];
			}

			return new static($post->ID);
		}
	}


?>