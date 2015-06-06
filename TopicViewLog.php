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

	void TopicViewLog()
		// !!!

	array list_get_tvl_members(int start, int items_per_page, raw sort)
		// !!!

	int list_get_tvl_num_members()
		// !!!

	void tvl_log()
		// !!!

*/

function TopicViewLog()
{
	global $smcFunc, $context, $user_info, $scripturl, $sourcedir, $txt, $topic;

	if (empty($topic))
		fatal_lang_error('no_board', false);

	$request = $smcFunc['db_query']('', '
		SELECT id_member_started
		FROM {db_prefix}topics
		WHERE id_topic = {int:topic} LIMIT 1',
		array(
			'topic' => $topic,
		)
	);
	list ($starter) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	if (!allowedTo('tvl_view_any') && $user_info['id'] == $starter)
		isAllowedTo('tvl_view_own');
	else
		isAllowedTo('tvl_view_any');

	$context['page_title'] = $txt['tvl_title'];

	require_once($sourcedir . '/Subs-List.php');

	$listOptions = array(
		'id' => 'tvl_list',
		'items_per_page' => 30,
		'base_href' => $scripturl . '?action=topicviewlog;topic=' . $topic,
		'default_sort_col' => 'time',
		'get_items' => array(
			'function' => 'list_get_tvl_members',
		),
		'get_count' => array(
			'function' => 'list_get_tvl_num_members',
		),
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['name'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="' . strtr($scripturl, array('%' => '%%')) . '?action=profile;u=%1$d">%2$s</a>',
						'params' => array(
							'id_member' => false,
							'real_name' => false,
						),
					),
					'style' => 'width: 25%;',
				),
				'sort' => array(
					'default' => 'real_name',
					'reverse' => 'real_name DESC',
				),
			),
			'group' => array(
				'header' => array(
					'value' => $txt['position'],
				),
				'data' => array(
					'db' => 'group_name',
					'style' => 'width: 25%;',
				),
				'sort' =>  array(
					'default' => 'group_name',
					'reverse' => 'group_name DESC',
				),
			),
			'posts' => array(
				'header' => array(
					'value' => $txt['posts'],
				),
				'data' => array(
					'db' => 'topic_posts',
					'style' => 'width: 10%; text-align: center;',
				),
				'sort' =>  array(
					'default' => 'topic_posts',
					'reverse' => 'topic_posts DESC',
				),
			),
			'views' => array(
				'header' => array(
					'value' => $txt['views'],
				),
				'data' => array(
					'db' => 'views',
					'style' => 'width: 10%; text-align: center;',
				),
				'sort' =>  array(
					'default' => 'views',
					'reverse' => 'views DESC',
				),
			),
			'time' => array(
				'header' => array(
					'value' => $txt['tvl_times'],
				),
				'data' => array(
					'function' => create_function('$rows', '
						return timeformat($rows[\'time\']);
					'),
					'style' => 'width: 30%;',
				),
				'sort' =>  array(
					'default' => 'time',
					'reverse' => 'time DESC',
				),
			),
		),
	);

	createList($listOptions);

	$context['sub_template'] = 'show_list';
	$context['default_list'] = 'tvl_list';
}

function list_get_tvl_members($start, $items_per_page, $sort)
{
	global $smcFunc, $topic;

	$request = $smcFunc['db_query']('', '
		SELECT
			mem.id_member, mem.member_name, mem.real_name, mg.group_name,
			tvl.views, tvl.time, COUNT(m.id_msg) AS topic_posts
		FROM {db_prefix}log_topic_view AS tvl
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = tvl.id_member)
			LEFT JOIN {db_prefix}membergroups AS mg ON (mg.id_group = CASE WHEN mem.id_group = {int:regular_id_group} THEN mem.id_post_group ELSE mem.id_group END)
			LEFT JOIN {db_prefix}messages AS m ON (m.id_member = mem.id_member AND m.id_topic = tvl.id_topic)
		WHERE tvl.id_topic = {int:topic}
		GROUP BY mem.id_member
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:per_page}',
		array(
			'sort' => $sort,
			'start' => $start,
			'per_page' => $items_per_page,
			'topic' => $topic,
			'regular_id_group' => 0,
		)
	);

	$rows = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$rows[] = $row;
	$smcFunc['db_free_result']($request);

	return $rows;
}

function list_get_tvl_num_members()
{
	global $smcFunc, $topic;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}log_topic_view
		WHERE id_topic = {int:topic}',
		array(
			'topic' => $topic,
		)
	);
	list ($num_rows) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $num_rows;
}

function tvl_log()
{
	global $smcFunc, $user_info, $topic;

	if (empty($topic) || $user_info['is_guest'])
		return false;

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}log_topic_view
		SET views = views + {int:views}, time = {int:time}
		WHERE id_member = {int:member}
			AND id_topic = {int:topic}
		LIMIT 1',
		array(
			'member' => $user_info['id'],
			'topic' => $topic,
			'views' => !empty($_SESSION['last_read_topic']) && $_SESSION['last_read_topic'] == $topic ? 0 : 1,
			'time' => time(),
		)
	);

	if ($smcFunc['db_affected_rows']() == 0)
	{
		$smcFunc['db_insert']('ignore',
			'{db_prefix}log_topic_view',
			array('id_member' => 'int', 'id_topic' => 'int', 'views' => 'int', 'time' => 'int'),
			array($user_info['id'], $topic, 1, time()),
			array('id_member', 'id_topic')
		);
	}
}

?>