<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook('postbit', 'postbit_lastactive_run');

function postbit_lastactive_info()
{
	global $db, $lang;
	$lang->load("config_postbit_lastactive");
    return array(
        "name"          => $db->escape_string($lang->pla_plugin_name),
        "description"   => $db->escape_string($lang->pla_plugin_desc),
        "website"       => "https://github.com/SvePu/MyBB-Postbit-UserLastActiveTime",
        "author"        => "Svepu",
        "authorsite"    => "https://github.com/SvePu",
        "version"       => "1.0",
        "codename"      => "postbitlastactive",
        "compatibility" => "18*"
    );
}

function postbit_lastactive_is_installed()
{
	global $mybb;

	if(isset($mybb->settings['postbit_lastactive_enable']))
	{
		return true;
	}
	return false;
}

function postbit_lastactive_install()
{
	global $db, $mybb, $lang;
	
	$lang->load("config_postbit_lastactive");	
	$query_add = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query_add, "rows");
    $pla_setgroup = array(
		"name" 			=>	"postbit_lastactive_settings",
		"title" 		=>	$db->escape_string($lang->pla_settings_title),
		"description" 	=>	$db->escape_string($lang->pla_settings_title_desc),
		"disporder"		=> 	$rows+1,
		"isdefault" 	=>  0
	);
    $gid = $db->insert_query("settinggroups", $pla_setgroup);
	
	$setting_array = array(
		'postbit_lastactive_enable' => array(
			'title'			=> $db->escape_string($lang->pla_enable_title),
			'description'  	=> $db->escape_string($lang->pla_enable_title_desc),
			'optionscode'  	=> 'yesno',
			'value'        	=> 1,
			'disporder'		=> 1
		),
		'postbit_lastactive_groupselect' => array(
			'title'			=> $db->escape_string($lang->pla_groupselect_title),
			'description' 	=> $db->escape_string($lang->pla_groupselect_desc),
			'optionscode'  	=> 'groupselect',
			'value'        	=> '3,4,6',
			"disporder"		=> 2
		),
	);

	foreach($setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
	}
	
	rebuild_settings();
}

function postbit_lastactive_activate()
{
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'user_details\']}')."#i", "{\$post['user_details']}\n{\$post['lastactive']}");
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'user_details\']}')."#i", "{\$post['user_details']}\n{\$post['lastactive']}");
}

function postbit_lastactive_deactivate()
{
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("postbit", "#\n".preg_quote('{$post[\'lastactive\']}')."(\r?)#", '', 0);
	find_replace_templatesets("postbit_classic", "#\n".preg_quote('{$post[\'lastactive\']}')."(\r?)#", '', 0);
}

function postbit_lastactive_uninstall()
{
	global $db;
	
	$result = $db->simple_select('settinggroups', 'gid', "name = 'postbit_lastactive_settings'", array('limit' => 1));
	$pla_group = $db->fetch_array($result);
	
	if(!empty($pla_group['gid']))
	{
		$db->delete_query('settinggroups', "gid='{$pla_group['gid']}'");
		$db->delete_query('settings', "gid='{$pla_group['gid']}'");
		rebuild_settings();
	}	
}

function postbit_lastactive_run(&$post)
{	
	global $db, $lang, $mybb;
	if($mybb->settings['postbit_lastactive_enable'] == "1" && !empty($mybb->settings['postbit_lastactive_groupselect']))
	{
		$lang->load("postbit_lastactive");
		if($post['lastvisit'] == $post['lastactive'] && $post['uid'] != 0 && (is_member($mybb->settings['postbit_lastactive_groupselect']) || $mybb->settings['postbit_lastactive_groupselect'] == "-1"))
		{
			$lastactivetime = my_date('relative', $post['lastactive']);
			$post['lastactive'] = '<br />'.$lang->sprintf($db->escape_string($lang->pla_lastactive), $lastactivetime);
		}
		else
		{
			$post['lastactive'] = '';
		}
	}
	else
	{
		$post['lastactive'] = '';
	}
}
