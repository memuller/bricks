<?php $posts = get_posts(array('post_type' => $options['post_type']));?>
<select <?php html_attributes(array('name' => $name, 'id' => $id, 'class' => 'text')) ?>  <?php echo $html ?> <?php echo $validations ?> >
<?php foreach ($posts as $post) {?>
	<option value="<?php echo $post->ID ?>" <?php echo $object->$field == $post->ID ? ' selected' : ''?> >
		<?php echo $post->post_title ?>
	</option>
<?php } ?>
</select> 