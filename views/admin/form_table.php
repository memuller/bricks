		<?php foreach ($fields as $field => $options) { 
			require 'partials/field_handler.php'; ?>
			<div class='form-field'>
					<?php label($options['label'], $id  ) ?>

					<?php switch ($options['type']) {
						
						case 'boolean':
							require 'fields/boolean.php';
						break;
						
						case 'geo':
							$options['width'] = '95%';
							require 'fields/geo.php';
						break;

						case 'date':
							require 'fields/date.php';
						break;
						
						case 'post_type':
							require 'fields/post_type.php';	
						break;

						case 'text_area':
							require 'fields/text_area.php';	
						break;

						case 'term_taxonomy':
							require 'fields/term_taxonomy.php';
						break;

						case 'array':
							require 'fields/array.php';
						break;

						case 'set':
							require 'fields/set.php';
						break;

						default:
							require 'fields/default.php';	
						break;

					} ?>
					<?php if($options['type'] == 'text_area' || $options['type'] == 'geo'){ description($options['description']); } ?>
				</div>
		<?php } ?>
<?php require 'partials/custom_single_field.php' ?>