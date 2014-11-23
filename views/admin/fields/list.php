<select <?php html_attributes(array('name' => $name, 'id' => $id, 'class' => 'text')) ?>  <?php echo $html ?> <?php echo $validations ?> >
<?php foreach ($options['values'] as $val) {?>
	<option value="<?php echo $val ?>" <?php echo $value == $val ? ' selected' : ''?> >
		<?php echo $val ?>
	</option>
<?php } ?>
</select> 