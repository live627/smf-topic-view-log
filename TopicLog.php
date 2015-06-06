<?php
/**********************************************************************************
* TopicViewLog.php                                                        		    *
***********************************************************************************
* TopicViewLog                                                                    *
* =============================================================================== *
* Software Version:           TopicViewLog 1.1                                    *
* Software by:                Blue Dream (http://www.simpleportal.net)            *
* Copyright 2006-2008 by:     Blue Dream (http://www.simpleportal.net)            *
* Support, News, Updates at:  http://www.simplemachines.org                       *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by Simple Machines LLC.          *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "license.txt" file for details of the Simple Machines license.          *
* The latest version can always be found at http://www.simplemachines.org.        *
**********************************************************************************/

if (!defined('SMF'))
	die('Hacking attempt...');

/*
	This is the main file handling TopicViewLog.

	void TopicLog()
		// !!!

	int getLogEntry()
		// !!!

	void tvl_log()
		// !!!

*/

// Function collecting the logs.
function TopicLog()
{
	global $txt, $db_prefix, $context, $log_request, $scripturl, $ID_MEMBER;

	// Make sure that it is an integer.
	$topic_id = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

	// Surely we need an id.
	if(empty($topic_id))
		fatal_lang_error('tvl_no_topic_id', false);

	// Check if the topic really exist and the permission.
	$request = db_query("
		SELECT ID_MEMBER_STARTED, ID_BOARD
		FROM {$db_prefix}topics
		WHERE ID_TOPIC = {$topic_id}
		LIMIT 1", __FILE__, __LINE__);

	list ($member, $board) = mysql_fetch_row($request);
	mysql_free_result($request);

	// Stop here if no topic.
	if(empty($member))
		fatal_lang_error('tvl_no_topic', false);

	// Can you view the log, mister?
	if ($member != $ID_MEMBER)
		isAllowedTo('tvl_view_any', $board);
	elseif (!allowedTo('tvl_view_any', $board))
		isAllowedTo('tvl_view_own', $board);

	// Load the template.
	loadTemplate('TopicLog');

	// Set the page title.
	$context['page_title'] = $txt['tvl_title'];

	// Ways we can sort this thing...
	$sort_methods = array(
		'user' =>  array(
			'down' => 'm.realName ASC',
			'up' => 'm.realName DESC'
		),
		'group' =>  array(
			'down' => 'mg.groupName ASC',
			'up' => 'mg.groupName DESC'
		),
		'times' => array(
			'down' => 'tvl.views ASC',
			'up' => 'tvl.views DESC'
		),
		'last_view' => array(
			'down' => 'tvl.time ASC',
			'up' => 'tvl.time DESC'
		),
	);

	// Columns to show.
	$context['columns'] = array(
		'user' => array(
			'width' => '25%',
			'label' => $txt[35],
			'sortable' => true
		),
		'group' => array(
			'width' => '20%',
			'label' => $txt[87],
			'sortable' => true
		),
		'times' => array(
			'width' => '15%',
			'label' => $txt['tvl_times'],
			'sortable' => true
		),
		'last_view' => array(
			'width' => '40%',
			'label' => $txt['tvl_lastView'],
			'sortable' => true
		),
	);

	// Default the sort method to 'last_view' up.
	if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
		$_REQUEST['sort'] = $_REQUEST['desc'] = 'last_view';

	// Set some context values for each column.
	foreach ($context['columns'] as $col => $dummy)
	{
		$context['columns'][$col]['selected'] = $col == $_REQUEST['sort'];
		$context['columns'][$col]['href'] = $scripturl . '?action=topicviewlog;id=' . $topic_id . ';sort=' . $col;

		if (!isset($_REQUEST['desc']) && $col == $_REQUEST['sort'])
			$context['columns'][$col]['href'] .= ';desc';

		$context['columns'][$col]['link'] = '<a href="' . $context['columns'][$col]['href'] . '">' . $context['columns'][$col]['label'] . '</a>';
	}

	$context['sort_by'] = $_REQUEST['sort'];
	$context['sort_direction'] = !isset($_REQUEST['desc']) ? 'down' : 'up';

	// Get the total amount of entries.
	$request = db_query("
		SELECT COUNT(*)
		FROM {$db_prefix}log_topic_view
		WHERE id_topic = {$topic_id}", __FILE__, __LINE__);
	list ($totalLogs) = mysql_fetch_row($request);
	mysql_free_result($request);

	// Create the page index.
	$context['page_index'] = constructPageIndex($scripturl . '?action=topicviewlog;id=' . $topic_id . ';sort=' . $_REQUEST['sort'] . (isset($_REQUEST['desc']) ? ';desc' : ''), $_REQUEST['start'], $totalLogs, 20);
	$context['start'] = $_REQUEST['start'];

	// Get log values.
	$log_request = db_query("
		SELECT
			tvl.id_member AS ID_MEMBER, m.realName, IFNULL(pg.groupName, '') AS post_group,
			tvl.time, tvl.views, IFNULL(mg.groupName, '') AS member_group
		FROM {$db_prefix}log_topic_view AS tvl
			LEFT JOIN {$db_prefix}members AS m ON (m.ID_MEMBER = tvl.id_member)
			LEFT JOIN {$db_prefix}membergroups AS pg ON (pg.ID_GROUP = m.ID_POST_GROUP)
			LEFT JOIN {$db_prefix}membergroups AS mg ON (mg.ID_GROUP = m.ID_GROUP)
		WHERE tvl.id_topic = {$topic_id}
		ORDER BY " . $sort_methods[$_REQUEST['sort']][$context['sort_direction']] . "
		LIMIT {$context['start']}, 20", __FILE__, __LINE__);

	// Set the value of the callback function.
	$context['get_logs'] = 'getLogEntry';
}

// Call-back function for the template to retrieve a row of log data.
function getLogEntry($reset = false)
{
	global $scripturl, $log_request, $txt, $context;

	if ($log_request == false)
		return false;

	if (!($row = mysql_fetch_assoc($log_request)))
		return false;

	$output = array(
		'id' => $row['ID_MEMBER'],
		'name' => $row['realName'],
		'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['ID_MEMBER'] . '">' . $row['realName'] . '</a>',
		'member_group' => $row['member_group'],
		'post_group' => $row['post_group'],
		'times' => $row['views'],
		'lastView' => !empty($row['time']) ? timeformat($row['time']) : '-',
	);

	return $output;
}

function tvl_log()
{
	global $db_prefix, $user_info, $topic, $ID_MEMBER;

	if (empty($topic) || $user_info['is_guest'])
		return false;

	db_query("
		UPDATE {$db_prefix}log_topic_view
		SET time = " . time() . ",
			views = views + 1
		WHERE id_member = {$ID_MEMBER}
			AND id_topic = {$topic}
		LIMIT 1",
	__FILE__, __LINE__);

	if (db_affected_rows() == 0)
		db_query("
			INSERT IGNORE INTO {$db_prefix}log_topic_view
				(id_member, id_topic, views, time)
			VALUES ({$ID_MEMBER}, {$topic}, 1, " . time() . ")",
		__FILE__, __LINE__);
}

?>