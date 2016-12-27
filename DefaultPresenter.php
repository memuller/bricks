<?php
	class DefaultPresenter extends Presenter{

		static $includes = [];

		static function build(){
			$plugin = static::$namespace . 'Plugin';
			$class = get_called_class();
			if(is_admin()) return ;
			foreach (['scripts', 'styles'] as $resource) {
				$version_var = "${resource}_version";
				$extension = $resource == 'scripts' ? 'js' : 'css';

				if($plugin::$$version_var){
					$class::$$resource = [
						'main' => [ 'source' => "/assets/dist/bundle.${extension}",
							'from' => 'plugin', 'version' => $plugin::$$version_var ]
					];
					$class::$includes[]= [
						'is' => 'any', $resource => ['main']
					];
				}
			}
			parent::build();
		}
	}
?>
