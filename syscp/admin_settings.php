<?php

/**
 * This file is part of the SysCP project.
 * Copyright (c) 2003-2009 the SysCP Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.syscp.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Florian Lippert <flo@syscp.org>
 * @license    GPLv2 http://files.syscp.org/misc/COPYING.txt
 * @package    Panel
 * @version    $Id$
 */

define('AREA', 'admin');

/**
 * Include our init.php, which manages Sessions, Language etc.
 */

$need_db_sql_data = true;
$need_root_db_sql_data = true;
require ("./lib/init.php");

if(($page == 'settings' || $page == 'overview')
   && $userinfo['change_serversettings'] == '1')
{
	$settings_data = loadConfigArrayDir('./actions/admin/settings/');
	$settings = loadSettings(&$settings_data, &$db);
	
	if(isset($_POST['send'])
	   && $_POST['send'] == 'send')
	{
		if(processForm(&$settings_data, &$_POST, array('filename' => $filename, 'action' => $action, 'page' => $page)))
		{
			standard_success('settingssaved', '', array('filename' => $filename, 'action' => $action, 'page' => $page));
		}
	}
	else
	{
		$fields = buildForm(&$settings_data);
		eval("echo \"" . getTemplate("settings/settings") . "\";");
	}
}
elseif($page == 'rebuildconfigs'
       && $userinfo['change_serversettings'] == '1')
{
	if(isset($_POST['send'])
	   && $_POST['send'] == 'send')
	{
		$log->logAction(ADM_ACTION, LOG_INFO, "rebuild configfiles");
		inserttask('1');
		inserttask('4');
		inserttask('5');
		redirectTo('admin_index.php', array('s' => $s));
	}
	else
	{
		ask_yesno('admin_configs_reallyrebuild', $filename, array('page' => $page));
	}
}
elseif($page == 'updatecounters'
       && $userinfo['change_serversettings'] == '1')
{
	if(isset($_POST['send'])
	   && $_POST['send'] == 'send')
	{
		$log->logAction(ADM_ACTION, LOG_INFO, "updated resource-counters");
		$updatecounters = updateCounters(true);
		$customers = '';
		foreach($updatecounters['customers'] as $customerid => $customer)
		{
			eval("\$customers.=\"" . getTemplate("settings/updatecounters_row_customer") . "\";");
		}

		$admins = '';
		foreach($updatecounters['admins'] as $adminid => $admin)
		{
			eval("\$admins.=\"" . getTemplate("settings/updatecounters_row_admin") . "\";");
		}

		eval("echo \"" . getTemplate("settings/updatecounters") . "\";");
	}
	else
	{
		ask_yesno('admin_counters_reallyupdate', $filename, array('page' => $page));
	}
}
elseif($page == 'wipecleartextmailpws'
       && $userinfo['change_serversettings'] == '1')
{
	if(isset($_POST['send'])
	   && $_POST['send'] == 'send')
	{
		$log->logAction(ADM_ACTION, LOG_WARNING, "wiped all cleartext mail passwords");
		$db->query("UPDATE `" . TABLE_MAIL_USERS . "` SET `password`='' ");
		$db->query("UPDATE `" . TABLE_PANEL_SETTINGS . "` SET `value`='0' WHERE `settinggroup`='system' AND `varname`='mailpwcleartext'");
		redirectTo('admin_settings.php', array('s' => $s));
	}
	else
	{
		ask_yesno('admin_cleartextmailpws_reallywipe', $filename, array('page' => $page));
	}
}
elseif($page == 'wipequotas'
       && $userinfo['change_serversettings'] == '1')
{
	if(isset($_POST['send'])
	   && $_POST['send'] == 'send')
	{
		$log->logAction(ADM_ACTION, LOG_WARNING, "wiped all mailquotas");

		// Set the quota to 0 which means unlimited

		$db->query("UPDATE `" . TABLE_MAIL_USERS . "` SET `quota`='0' ");
		$db->query("UPDATE " . TABLE_PANEL_CUSTOMERS . " SET `email_quota_used` = 0");
		redirectTo('admin_settings.php', array('s' => $s));
	}
	else
	{
		ask_yesno('admin_quotas_reallywipe', $filename, array('page' => $page));
	}
}
elseif($page == 'enforcequotas'
       && $userinfo['change_serversettings'] == '1')
{
	if(isset($_POST['send'])
	   && $_POST['send'] == 'send')
	{
		// Fetch all accounts

		$result = $db->query("SELECT `quota`, `customerid` FROM " . TABLE_MAIL_USERS);

		while($array = $db->fetch_array($result))
		{
			$difference = $settings['system']['mail_quota'] - $array['quota'];
			$db->query("UPDATE " . TABLE_PANEL_CUSTOMERS . " SET `email_quota_used` = `email_quota_used` + " . (int)$difference . " WHERE `customerid` = '" . $array['customerid'] . "'");
		}

		// Set the new quota

		$db->query("UPDATE `" . TABLE_MAIL_USERS . "` SET `quota`='" . $settings['system']['mail_quota'] . "'");

		// Update the Customer, if the used quota is bigger than the allowed quota

		$db->query("UPDATE " . TABLE_PANEL_CUSTOMERS . " SET `email_quota` = `email_quota_used` WHERE `email_quota` < `email_quota_used`");
		$log->logAction(ADM_ACTION, LOG_WARNING, 'enforcing mailquota to all customers: ' . $settings['system']['mail_quota'] . ' MB');
		redirectTo('admin_settings.php', array('s' => $s));
	}
	else
	{
		ask_yesno('admin_quotas_reallyenforce', $filename, array('page' => $page));
	}
}

?>