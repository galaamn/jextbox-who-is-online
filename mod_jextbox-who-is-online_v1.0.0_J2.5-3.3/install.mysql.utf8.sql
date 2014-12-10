CREATE TABLE IF NOT EXISTS `#__jextboxwhoisonline_simulatedvisitors` (
  `count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Number of simulated visitors.',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Execution time of last simulation.',
  `session_duration` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Session duration.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
