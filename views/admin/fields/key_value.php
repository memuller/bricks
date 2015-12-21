<?php 
	$amount = $options['size']; $original_name = $name; $values = $value; 
	$keys = $value ? array_keys($value) : array();
?>
<?php description($options['description']) ?>
<div class='input key_value bricks' id='<?php echo $id ?>'>
	<?php for ($i=0; $i < $amount; $i++) {?>
		<?php 
			$key = isset($keys[$i]) ? $keys[$i] : '';
			$value = empty($key) ? '' : $values[$key];
			$name = $original_name."[$key]"
		?>
		<div class='input-group'>
			<input type="text" <?php html_attributes([ 
				'class' => 'key', 'value' => $key, 'data-index' => $i, 'data-id' => $original_name,
				'style' => 'width: 28%; display: inline-block;'
			])?>>
			<input type="text" <?php html_attributes([ 'class' => 'value', 'value' => $value, 'name' => $name,
				'style' => 'width: 70%; display: inline-block;'
			]) ?>>
		</div>
	<?php } ?>
</div>
