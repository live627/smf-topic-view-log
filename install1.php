<?php
################################
##	.LORD.
##	Topic View Log
##	v1.2
################################

global $db_prefix;

db_query("CREATE TABLE IF NOT EXISTS {$db_prefix}log_topic_view (
			id_member MEDIUMINT(8) UNSIGNED DEFAULT 0 NOT NULL,
			id_topic MEDIUMINT(8) UNSIGNED DEFAULT 0 NOT NULL,
			views INT(10) UNSIGNED DEFAULT 0 NOT NULL,
			time INT(10) UNSIGNED DEFAULT 0 NOT NULL,
				PRIMARY KEY (`id_member`, `id_topic`),
				INDEX (`id_topic`)
			)"
			,__FILE__, __LINE__
		);

?>