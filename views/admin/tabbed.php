<h2 class='nav-tabwrapper' style="border-bottom: 1px solid #ccc; padding-bottom: 0px;" > <?php $i = !isset($i) ? 0 : $i ?>
	<?php foreach ($tabs as $tab => $fields): ?>
		<a class="nav-tab <?php echo $i == 0 ? 'nav-tab-active' : ''  ?> <?php echo isset($data) ? $data[$i] : '' ?>"
			href="#tab-<?php echo $i ?>"
		><?php echo $tab ?></a>		
	<?php $i++; endforeach; ?>
</h2>
<div id="tabs">	
	<?php $i = 0 ; foreach ($tabs as $tab => $field_names): 
		$fields = array();
		foreach ($field_names as $name) {
			$fields[$name]=$class::$fields[$name];
		}
		?>
		<div id="tab-<?php echo $i ?>" class="tab content">
			<?php $presenter::render('admin/metabox', array(
				'type' => $type, 
				'object' => $object,
				'fields' => $fields,
				'description_colspan' => false,
				'style' => 'margin-bottom: 15px;'
			));	?>
		</div>
	<?php $i++; endforeach; ?>
</div>