<?php
/**
 *	Main plugin file Postbit UserLastActiveTime plugin for MyBB 1.8
 *	
 *	Copyright © 2015 Svepu
 *	Last change: 2015-12-03
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook('postbit', 'postbit_lastactive_run');
$plugins->add_hook('postbit_prev', 'postbit_lastactive_run');
$plugins->add_hook('postbit_pm', 'postbit_lastactive_run');
$plugins->add_hook('postbit_announcement', 'postbit_lastactive_run');

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
        "version"       => "1.2",
        "codename"      => "postbitlastactive",
        "compatibility" => "18*"
    );
}

function postbit_lastactive_is_installed()
{
	global $mybb;

	if(isset($mybb->settings['pla_enable']))
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
		"name" 			=>	"pla_settings",
		"title" 		=>	$db->escape_string($lang->pla_settings_title),
		"description" 	=>	$db->escape_string($lang->pla_settings_title_desc),
		"disporder"		=> 	$rows+1,
		"isdefault" 	=>  0
	);
    $gid = $db->insert_query("settinggroups", $pla_setgroup);
	
	$setting_array = array(
		'pla_enable' => array(
			'title'			=> $db->escape_string($lang->pla_enable_title),
			'description'  	=> $db->escape_string($lang->pla_enable_title_desc),
			'optionscode'  	=> 'yesno',
			'value'        	=> 1,
			'disporder'		=> 1
		),
		'pla_groupselect' => array(
			'title'			=> $db->escape_string($lang->pla_groupselect_title),
			'description' 	=> $db->escape_string($lang->pla_groupselect_desc),
			'optionscode'  	=> 'groupselect',
			'value'        	=> '3,4,6',
			"disporder"		=> 2
		),
		'pla_timeformat' => array(
			'title'			=> $db->escape_string($lang->pla_timeformat_title),
			'description' 	=> $db->escape_string($lang->pla_timeformat_desc),
			'optionscode'  	=> 'text',
			'value'        	=> $db->escape_string($lang->pla_timeformat_default),
			"disporder"		=> 3
		),
		'pla_showonlinestatus' => array(
			'title'			=> $db->escape_string($lang->pla_showonlinestatus_title),
			'description' 	=> $db->escape_string($lang->pla_showonlinestatus_desc),
			'optionscode'  	=> 'yesno',
			'value'        	=> 0,
			"disporder"		=> 4
		),
		'pla_onlinestatus_text' => array(
			'title'			=> $db->escape_string($lang->pla_onlinestatus_text_title),
			'description' 	=> $db->escape_string($lang->pla_onlinestatus_text_desc),
			'optionscode'  	=> 'text',
			'value'        	=> 'Online',
			"disporder"		=> 5
		),
		'pla_onlinestatus_style' => array(
			'title'			=> $db->escape_string($lang->pla_onlinestatus_style_title),
			'description' 	=> $db->escape_string($lang->pla_onlinestatus_style_desc),
			'optionscode'  	=> 'textarea',
			'value'        	=> 'color:green;\nfont-weight:bold;',
			"disporder"		=> 6
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
	
}

function postbit_lastactive_deactivate()
{
	
}

function postbit_lastactive_uninstall()
{
	global $db;
	
	$result = $db->simple_select('settinggroups', 'gid', "name = 'pla_settings'", array('limit' => 1));
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
	if($mybb->settings['pla_enable'] == "1" && !empty($mybb->settings['pla_groupselect']) && $post['uid'] != 0)
	{		
		$lang->load("postbit_lastactive");
		if($post['lastvisit'] == $post['lastactive'] && (is_member($mybb->settings['pla_groupselect']) || $mybb->settings['pla_groupselect'] == "-1"))
		{
			if(empty($mybb->settings['pla_timeformat']))
			{
				$mybb->settings['pla_timeformat'] = "relative";
			}
			$lastactivetime = my_date($mybb->settings['pla_timeformat'], $post['lastactive']);
			$postlastactive = '<br />'.$lang->sprintf($db->escape_string($lang->pla_lastactive), $lastactivetime);
		}
		elseif($post['lastvisit'] != $post['lastactive'] && (is_member($mybb->settings['pla_groupselect']) || $mybb->settings['pla_groupselect'] == "-1") && $mybb->settings['pla_showonlinestatus'] == "1")
		{
			$onlinetextstring = empty($mybb->settings['pla_onlinestatus_text']) ? 'Online' : $mybb->settings['pla_onlinestatus_text'];
			$onlinecssstyle = '';
			if(!empty($mybb->settings['pla_onlinestatus_style']))
			{
				$onlinecssstylesheet = preg_replace("%(\r\n)|(\r)%", "", $mybb->settings['pla_onlinestatus_style']);
				$onlinecssstyle = 'style="'.$onlinecssstylesheet.'"';
			}			
			$postlastactive = '<br /><span '.$onlinecssstyle.'>'.$onlinetextstring.'</span>';
		}
		else
		{
			$postlastactive = '';
		}
		
		$post['user_details'] = $post['user_details'].$postlastactive;
	}
	return $post;
}
