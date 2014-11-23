<?php 
	$params = array('post_type' => $options['post_type']);
	if(isset($options['filter'])) $params = array_merge($params, $options['filter']);
	$posts = get_posts($params);
?>
<select <?php html_attributes(array('name' => $name, 'id' => $id, 'class' => 'text')) ?>  <?php echo $html ?> <?php echo $validations ?> >
<?php foreach ($posts as $post) {?>
	<option value="<?php echo $post->ID ?>" <?php echo $value == $post->ID ? ' selected' : ''?> >
		<?php echo $post->post_title ?>
	</option>
<?php } ?>
</select> 