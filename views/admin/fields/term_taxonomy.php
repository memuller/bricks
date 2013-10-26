<?php $terms = get_terms($options['taxonomy'], array('get' => 'all')); ;?>
<select <?php html_attributes(array('name' => $name, 'id' => $id, 'class' => 'text')) ?>  <?php echo $html ?> <?php echo $validations ?> >
<?php foreach ($terms as $term) {?>
	<option value="<?php echo $term->term_id ?>" <?php echo $object->$field == $term->term_id ? ' selected' : ''?> >
		<?php echo $term->name ?>
	</option>
<?php } ?>
</select> 