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

class ModJExtBOXWhoisOnlineHelper{

	public static function getOnlineCount(){

		$result	= array();
		$user = 0;
		$guest = 0;
		$db	= JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('guest'))
			->from($db->quoteName('#__session'))
			->where($db->quoteName('client_id') . '=' . $db->quote(0));
		$db->setQuery($query);
		$visitors = (array) $db->loadObjectList();
		if(count($visitors)){
			foreach($visitors as $visitor){
				if($visitor->guest == 1){
					$guest ++;
				}
				if($visitor->guest == 0){
					$user ++;
				}
			}
		}
		$result['user'] = $user;
		$result['guest'] = $guest;
		return $result;

	}

	public static function getOnlineUserNames($params){

		$db	= JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('a.username', 'a.time', 'a.userid', 'a.client_id')))
			->from($db->quoteName('#__session') . 'AS' . $db->quoteName('a'))
			->where($db->quoteName('a.userid') . ' != 0')
			->where($db->quoteName('a.client_id') . ' = 0')
			->group($db->quoteName(array('a.username', 'a.time', 'a.userid', 'a.client_id')));
		$user = JFactory::getUser();
		if(!$user->authorise('core.admin') && $params->get('filter_groups', 0) == 1){
			$groups = $user->getAuthorisedGroups();
			if(empty($groups)){
				return array();
			}
			$query->join('LEFT', '#__user_usergroup_map AS m ON m.user_id = a.userid')
				->join('LEFT', '#__usergroups AS ug ON ug.id = m.group_id')
				->where('ug.id in (' . implode(',', $groups) . ')')
				->where('ug.id <> 1');
		}
		$db->setQuery($query);
		return (array) $db->loadObjectList();

	}

	public static function getSimulatedCount($params){

		// Getting information of last simulation
		$last_simulation = self::get_last_simulation();

		// Common or important variables
		$simulated_count = 0;
		$current_session_duration = self::get_current_session_duration();
		$current_simulation_start_time = time() - $current_session_duration * 60;

		// Remove expired visitors
		if(strtotime($last_simulation->time) < time()){
			$last_simulation_start_time = max(0, strtotime($last_simulation->time) - $last_simulation->session_duration * 60);
			$duration = ($current_simulation_start_time - $last_simulation_start_time) / 60;
			if($duration > 0 && $duration < $current_session_duration){
				$simulated_count += $last_simulation->count;
				$parameter = self::get_parameter_simulation($params, $last_simulation_start_time);
				$simulated_count -= self::get_simulated_number_of_visitors($parameter, $duration);
				$simulated_count = max(0, $simulated_count);
			}
		}

		// Add new visitors
		if(($duration = min($current_session_duration, max(0, (time() - strtotime($last_simulation->time)) / 60))) > 0){
			$parameter = self::get_parameter_simulation($params, $current_simulation_start_time);
			$simulated_count += self::get_simulated_number_of_visitors($parameter, $duration);
		}

		// Setting information of current simulation
		self::set_last_simulation($simulated_count, $current_session_duration, $last_simulation->time == '0000-00-00 00:00:00');

		return $simulated_count;

	}

	private static function get_current_session_duration(){

		$config = JFactory::getConfig();
		return $config->get('lifetime');

	}

	private static function get_last_simulation(){

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select($db->quoteName(array('time', 'count', 'session_duration')))
			->from($db->quoteName('#__jextboxwhoisonline_simulatedvisitors'));
		$db->setQuery($query, 0, 1);
		$last_simulation = $db->loadObject();
		if(is_null($last_simulation)){
			$last_simulation = new stdClass;
			$last_simulation->count = 0;
			$last_simulation->time = '0000-00-00 00:00:00';
			$last_simulation->session_duration = 0;
		}
		return $last_simulation;

	}

	private static function set_last_simulation($count, $session_duration, $first){

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		if($first){
			$query
				->insert($db->quoteName('#__jextboxwhoisonline_simulatedvisitors'))
				->columns($db->quoteName(array('count', 'time', 'session_duration')))
				->values($db->quote($count).','.$db->quote(date('Y-m-d H:i:s')).','.$db->quote($session_duration));
		}else{
			$fields = array(
				$db->quoteName('count') . '=' . $db->quote($count),
				$db->quoteName('time') . '=' . $db->quote(date('Y-m-d H:i:s')),
				$db->quoteName('session_duration') . '=' . $db->quote($session_duration)
			);
			$query
				->update($db->quoteName('#__jextboxwhoisonline_simulatedvisitors'))
				->set($fields);
		}
		$db->setQuery($query);
		$db->query();

	}

	private static function get_parameter_simulation($params, $time){

		$average_weekly_visitors = $params->get('average_weekly_visitors', 10000);
		$visitors_by_day_as_percent = explode(',', $params->get('visitors_by_day_as_percent', '15.93,18.36,17.40,16.55,11.83,8.49,11.44'));
		$hits_by_day_as_percent = array(
			'Monday' => $visitors_by_day_as_percent[0],
			'Tuesday' => $visitors_by_day_as_percent[1],
			'Wednesday' => $visitors_by_day_as_percent[2],
			'Thursday' => $visitors_by_day_as_percent[3],
			'Friday' => $visitors_by_day_as_percent[4],
			'Saturday' => $visitors_by_day_as_percent[5],
			'Sunday' => $visitors_by_day_as_percent[6]
		);
		$visitors_by_hour_as_percent = explode(',', $params->get('visitors_by_hour_as_percent', '5.36,4.58,4.61,5.48,5.29,5.76,5.01,5.29,5.25,5.77,5.00,4.59,3.87,4.09,3.45,2.97,3.28,3.10,2.79,2.61,2.58,3.30,3.34,2.50'));
		$average_number_of_visitors_in_current_day = $average_weekly_visitors * $hits_by_day_as_percent[date('l', $time)] / 7;
		$average_number_of_visitors_in_current_hour = $average_number_of_visitors_in_current_day * $visitors_by_hour_as_percent[date('G', $time)] / 24;
		$average_number_of_visitors_in_a_minute = $average_number_of_visitors_in_current_hour / 60;
		return $average_number_of_visitors_in_a_minute;

	}

	private static function get_simulated_number_of_visitors($parameter, $duration){

		if($duration <= 1){
			$parameter *= $duration;
		}
		$limit = exp(- $parameter);
		$randmax = mt_getrandmax();
		$n = 0;
		$a = 1;
		while(($a *= mt_rand()/$randmax) >= $limit):
			$n ++;
		endwhile;
		if($duration > 1){
			$n = floor($n * $duration);
		}
		return $n;

	}

}
