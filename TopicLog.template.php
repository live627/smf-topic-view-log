<?php
// Version: 1.0; TopicLog

function template_main()
{
	global $context, $settings, $txt;

	// Start the table and show the page index.
	echo '
		<table border="0" align="center" cellspacing="1" cellpadding="4" class="bordercolor" width="70%">
			<tr class="catbg3">
				<td colspan="4"><b>', $txt[139], ':</b> ', $context['page_index'], '</td>
			</tr><tr class="titlebg">';

	// Cycle through the columns to show.
	foreach ($context['columns'] as $column)
	{
		if ($column['selected'])
			echo '
				<th', isset($column['width']) ? ' width="' . $column['width'] . '"' : '', '>
					<a href="', $column['href'], '">', $column['label'], ' <img src="', $settings['images_url'], '/sort_', $context['sort_direction'], '.gif" alt="" /></a>
				</th>';
		elseif ($column['sortable'])
			echo '
				<th', isset($column['width']) ? ' width="' . $column['width'] . '"' : '', '>
					', $column['link'], '
				</th>';
		else
			echo '
				<th', isset($column['width']) ? ' width="' . $column['width'] . '"' : '', '>
					', $column['label'], '
				</th>';
	}
	echo '
			</tr>';

	// Time to echo the data.
	while ($log = $context['get_logs']())
	{
		echo '
			<tr>
				<td align="left" valign="top" class="windowbg">', $log['link'], '</td>
				<td align="left" valign="top" class="windowbg2">', empty($log['member_group']) ? $log['post_group'] : $log['member_group'], '</td>
				<td align="center" valign="top" class="windowbg2">', $log['times'], '</td>
				<td align="center" valign="top" class="windowbg2">', $log['lastView'], '</td>
			</tr>';
	}

	// End the table with another page index.
	echo '
			<tr class="catbg3">
				<td colspan="4"><b>', $txt[139], ':</b> ', $context['page_index'], '</td>
			</tr>
		</table>';
}

?>