<?php  global $metabox_placing ; $metabox_placing = $placing ;
	foreach ($fields as $field => $options) {
		if($options['type'] == 'hidden'){
			require 'partials/field_handler.php' ;
			require 'fields/hidden.php' ;
			unset($fields[$field]) ;
		}
	}
	if(!isset($description_colspan)) $description_colspan = true ;
?>
<table class='form-table' <?php if(isset($style)) echo "style='$style'" ;?>>
	<tbody>
		<?php $i = 0; foreach ($fields as $field => $options) { 
			require 'partials/field_handler.php'; 
			$description_placing = ($options['type'] == 'text_area' || $options['type'] == 'geo' || $options['type'] == 'editor') ? 'top' : 'bottom' ; ?>
			<?php
				if($description_colspan){  
					$header_style = $i == 0 ? 'padding-top: 5px; ' : '';
					$header_style .= $description_placing != 'top' ? 'border-bottom: 0px; padding-bottom: 5px;' : '' ;
				}
			?>
			<tr>
				<th <?php echo "style='$header_style'" ?>>
					<?php label($options['label'], $id  ) ?>
					<?php if($description_placing == 'top'){ description($options['description']); } ?>
				</th>
				<td <?php echo "style='$header_style'" ?>>
					<?php switch ($options['type']) {
						
						case 'multiple':
							require 'fields/multiple.php';
						break;

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

						case 'list':
							require 'fields/list.php';
						break;

						case 'set':
							require 'fields/set.php';
						break;
						
						case 'media':
							require 'fields/media.php';
						break;
						case 'editor':
							require 'fields/editor.php';
						break;
						default:
							require 'fields/default.php';	
						break;

					} ?>
					<?php if(!$description_colspan && $description_placing != 'top') description($options['description']) ; ?>
				</td>
				<?php if ($description_colspan && $description_placing != 'top'): ?>
					</tr>
					<tr class="compact"><td colspan='2' style="padding: 2px 5px 8px 5px ;">
						<?php description($options['description']) ?>
					</td>					
				<?php endif ?>
			</tr>
		<?php $i ++ ; } ?>
	</tbody>
</table>

<?php require 'partials/custom_single_field.php' ?>