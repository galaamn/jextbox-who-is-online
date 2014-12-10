<?php

/**
* @package     JExtBOX Who is Online
* @author      Galaa
* @publisher   JExtBOX - BOX of Joomla Extensions (www.jextbox.com)
* @copyright   Copyright (C) 2013 Galaa
* @authorUrl   http://galaa.mn
* @authorEmail contact@galaa.mn
* @license     This extension in released under the GNU/GPL License - http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

// Include the whosonline functions only once
require_once __DIR__ . '/helper.php';

$showmode = $params->get('showmode', 0);

if($showmode == 0 || $showmode == 2){
	$count = ModJExtBOXWhoisOnlineHelper::getOnlineCount();
	if($params->get('including_simulated_number_of_visitors', 1) == 1){
		$count['guest'] += ModJExtBOXWhoisOnlineHelper::getSimulatedCount($params);
	}
}

if($showmode > 0){
	$names = ModJExtBOXWhoisOnlineHelper::getOnlineUserNames($params);
}

$linknames = $params->get('linknames', 0);
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));

require JModuleHelper::getLayoutPath('mod_jextboxwhoisonline', $params->get('layout', 'default'));
