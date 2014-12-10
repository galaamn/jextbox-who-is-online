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
?>

<?php if($showmode == 0 || $showmode == 2) : ?>
	<?php $guest = JText::plural('MOD_JEXTBOXWHOISONLINE_GUESTS', $count['guest']); ?>
	<?php $member = JText::plural('MOD_JEXTBOXWHOISONLINE_MEMBERS', $count['user']); ?>
	<p><?php echo JText::sprintf('MOD_JEXTBOXWHOISONLINE_WE_HAVE', $guest, $member); ?></p>
<?php endif; ?>

<?php if(($showmode > 0) && count($names)) : ?>
	<ul class="whosonline<?php echo $moduleclass_sfx ?>" >
	<?php if($params->get('filter_groups')) : ?>
		<p><?php echo JText::_('MOD_JEXTBOXWHOISONLINE_SAME_GROUP_MESSAGE'); ?></p>
	<?php endif;?>
	<?php foreach($names as $name) : ?>
		<li>
			<?php echo $name->username; ?>
		</li>
	<?php endforeach; ?>
	</ul>
<?php endif; ?>
