<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
{
	$ssi = true;
	require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('SMF'))
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
if (!array_key_exists('db_add_column', $smcFunc))
	db_extend('packages');

    // Creating tables
	$tables = array(
		
		'log_topic_view' => array(
			'name' => 'log_topic_view',
			//Columns
			'columns' => array(
				array(
					'name' => 'id_member',
					'type' => 'mediumint',					
					'size' => '8',
					'default' => '0',
				),							   								
				array(
					'name' => 'id_topic',
					'type' => 'mediumint',					
					'size' => '8',
					'default' => '0',								
				),
					
				array(
					'name' => 'views',
					'type' => 'int',					
					'size' => '10',
					'default' => '0',							
				),

				array(
					'name' => 'time',
					'type' => 'int',					
					'size' => '10',
					'default' => '0',
					
				),		
			),
			//End Columns
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_member', 'id_topic')
				),
				array(
					'type' => 'key',
					'columns' => array('id_topic')
				),
			)
		),		
		//End Table
					
	);	

	//Creating Tables
	foreach ($tables as $table)
	{
		$table_name = $table['name'];
		$smcFunc['db_create_table']('{db_prefix}' . $table_name, $table['columns'], $table['indexes']);		
		$currentTable = $smcFunc['db_table_structure']('{db_prefix}' . $table_name);
		// Check that all columns are in
		foreach ($table['columns'] as $id => $col)
		{
			$exists = false;
			// TODO: Check that definition is correct
			foreach ($currentTable['columns'] as $col2)
			{
				if ($col['name'] === $col2['name'])
				{
					$exists = true;
					break;
				}
			}

			// Add missing columns
			if (!$exists)
				$smcFunc['db_add_column']('{db_prefix}' . $table_name, $col);

			//Check, not change anything?
			if($exists)
			{
				$smcFunc['db_change_column']('{db_prefix}' . $table_name, $col['name'], $col);
			}

		}
		//End add missing columns
		// Check that all indexes are in and correct
		if ($table['indexes'] > 0)
		{
			foreach ($table['indexes'] as $id => $index)
			{
				$exists = false;
	
				foreach ($currentTable['indexes'] as $index2)
				{
					// Primary is special case
					if ($index['type'] == 'primary' && $index2['type'] == 'primary')
					{
						$exists = true;
	
						if ($index['columns'] !== $index2['columns'])
						{
							$smcFunc['db_remove_index']('{db_prefix}' . $table_name, 'primary');
							$smcFunc['db_add_index']('{db_prefix}' . $table_name, $index);
						}
	
						break;
					}
					// Make sure index is correct
					elseif (isset($index['name']) && isset($index2['name']) && $index['name'] == $index2['name'])
					{
						$exists = true;
	
						// Need to be changed?
						if ($index['type'] != $index2['type'] || $index['columns'] !== $index2['columns'])
						{
							$smcFunc['db_remove_index']('{db_prefix}' . $table_name, $index['name']);
							$smcFunc['db_add_index']('{db_prefix}' . $table_name, $index);
						}
	
						break;
					}
				}
	
				if (!$exists)
					$smcFunc['db_add_index']('{db_prefix}' . $table_name, $index);
			}
		}
		//End check indexes
	}

	// OK, time to report, output all the stuff to be shown to the user
	if ($manual_install){
echo '
<table cellpadding="0" cellspacing="0" border="0" class="tborder" width="800" align="center"><tr><td>
<div class="titlebg" style="padding: 1ex" align="center">
	BD CREATED! WWW.SMFSIMPLE.COM!
</div>
</td></tr></table>
<br />
</body></html>';
    }

?>