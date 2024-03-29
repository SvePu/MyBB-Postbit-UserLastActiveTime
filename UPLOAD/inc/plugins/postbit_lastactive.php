<?php

/**
 *  Main plugin file Postbit UserLastActiveTime plugin for MyBB 1.8
 *
 *  Copyright: 2015 -> @Svepu
 *  Last change: 2019-11-28
 */

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

if (defined('IN_ADMINCP'))
{
    $plugins->add_hook('admin_config_settings_begin', 'postbit_lastactive_settings');
    $plugins->add_hook("admin_settings_print_peekers", 'postbit_lastactive_settings_peekers');
}
else
{
    $plugins->add_hook('postbit', 'postbit_lastactive_run');
    $plugins->add_hook('postbit_prev', 'postbit_lastactive_run');
    $plugins->add_hook('postbit_pm', 'postbit_lastactive_run');
    $plugins->add_hook('postbit_announcement', 'postbit_lastactive_run');
}

function postbit_lastactive_info()
{
    global $plugins_cache, $db, $lang;
    $lang->load("config_postbit_lastactive");

    $info = array(
        "name"          => $db->escape_string($lang->postbitlastactive),
        "description"   => $db->escape_string($lang->postbitlastactive_desc),
        "website"       => "https://github.com/SvePu/MyBB-Postbit-UserLastActiveTime",
        "author"        => "Svepu",
        "authorsite"    => "https://github.com/SvePu",
        "version"       => "1.4",
        "codename"      => "postbitlastactive",
        "compatibility" => "18*"
    );

    if (is_array($plugins_cache) && is_array($plugins_cache['active']) && array_key_exists('postbit_lastactive', $plugins_cache['active']))
    {
        $query = $db->simple_select('settinggroups', 'gid', "name = 'postbitlastactive'", array('limit' => 1));
        $settings_group = (int)$db->fetch_field($query, 'gid');
        if ($settings_group)
        {
            if (!isset($lang->plugin_settings))
            {
                $lang->load("config_settings");
            }

            $info['description'] .=  "<br /><span style=\"line-height: 2.5em;display: inline-block;font-weight: 600;font-style: italic;\"><a href=\"index.php?module=config-settings&amp;action=change&amp;gid=" . $settings_group . "\"><img style=\"vertical-align: sub;\" src=\"./styles/default/images/icons/custom.png\" title=\"" . $db->escape_string($lang->plugin_settings) . "\" alt=\"settings_icon\" width=\"16\" height=\"16\" />&nbsp;" . $db->escape_string($lang->plugin_settings) . "</a></span>";
        }
    }

    return $info;
}

function postbit_lastactive_install()
{
    global $db, $mybb, $lang;

    $lang->load("config_postbit_lastactive");

    $query = $db->simple_select('settinggroups', 'MAX(disporder) AS disporder');
    $disporder = (int)$db->fetch_field($query, 'disporder');

    $setting_group = array(
        'name' => 'postbitlastactive',
        "title" => $db->escape_string($lang->setting_group_postbitlastactive),
        "description" => $db->escape_string($lang->setting_group_postbitlastactive_desc),
        'isdefault' => 0
    );

    $setting_group['disporder'] = ++$disporder;

    $gid = (int)$db->insert_query('settinggroups', $setting_group);

    $settings = array(
        'enable' => array(
            'optionscode'   => 'yesno',
            'value'         => 1
        ),
        'groupselect' => array(
            'optionscode'   => 'groupselect',
            'value'         => '3,4,6'
        ),
        'timeformat' => array(
            'optionscode'   => 'text',
            'value'         => 'Y-m-d, g:i a'
        ),
        'showonlinestatus' => array(
            'optionscode'   => 'yesno',
            'value'         => 1
        ),
        'onlinecutofftime' => array(
            'optionscode'   => 'numeric \nmin=1',
            'value'         => 5
        ),
        'onlinestatus_text' => array(
            'optionscode'   => 'text',
            'value'         => 'Online'
        ),
        'onlinestatus_style' => array(
            'optionscode'   => 'textarea',
            'value'         => 'color:green;\nfont-weight:bold;'
        )
    );

    $disporder = 0;

    foreach ($settings as $name => $setting)
    {
        $name = "postbitlastactive_{$name}";

        $setting['name'] = $db->escape_string($name);

        $lang_var_title = "setting_{$name}";
        $lang_var_description = "setting_{$name}_desc";

        $setting['title'] = $db->escape_string($lang->{$lang_var_title});
        $setting['description'] = $db->escape_string($lang->{$lang_var_description});
        $setting['disporder'] = $disporder;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
        ++$disporder;
    }

    rebuild_settings();
}

function postbit_lastactive_is_installed()
{
    global $mybb;
    if (isset($mybb->settings['postbitlastactive_enable']))
    {
        return true;
    }
    return false;
}

function postbit_lastactive_uninstall()
{
    global $db;

    $db->delete_query("settinggroups", "name='postbitlastactive'");
    $db->delete_query("settings", "name LIKE 'postbitlastactive_%'");

    rebuild_settings();
}

function postbit_lastactive_run(&$post)
{
    global $lang, $mybb;
    if ($mybb->settings['postbitlastactive_enable'] != "1" || $post['uid'] == 0 || $post['usergroup'] == 1 || empty($mybb->settings['postbitlastactive_groupselect']))
    {
        return;
    }
    if (!is_member($mybb->settings['postbitlastactive_groupselect']) && $mybb->settings['postbitlastactive_groupselect'] != "-1")
    {
        return;
    }

    $lang->load("postbit_lastactive");
    $post['last_active_info'] = "";

    if ($post['invisible'] != 1 || $mybb->usergroup['canviewwolinvis'] == 1)
    {
        if ($mybb->settings['postbitlastactive_showonlinestatus'] == 1 && $post['lastvisit'] != $post['lastactive'])
        {
            $cutofftime = $mybb->settings['postbitlastactive_onlinecutofftime'] * 60;

            if ($post['lastactive'] > TIME_NOW - $cutofftime)
            {
                if (empty($mybb->settings['postbitlastactive_onlinestatus_text']))
                {
                    $mybb->settings['postbitlastactive_onlinestatus_text'] = "Online";
                }

                $onlinecssstyle = '';
                if (!empty($mybb->settings['postbitlastactive_onlinestatus_style']))
                {
                    $mybb->settings['postbitlastactive_onlinestatus_style'] = preg_replace("%(\r\n)|(\r)%", "", $mybb->settings['postbitlastactive_onlinestatus_style']);
                    $onlinecssstyle = 'style="' . $mybb->settings['postbitlastactive_onlinestatus_style'] . '"';
                }
                $post['last_active_info'] = '<br /><span ' . $onlinecssstyle . '>' . $mybb->settings['postbitlastactive_onlinestatus_text'] . '</span>';
            }
            else
            {
                if (empty($mybb->settings['postbitlastactive_timeformat']))
                {
                    $mybb->settings['postbitlastactive_timeformat'] = "relative";
                }
                $lastactivetime = my_date($mybb->settings['postbitlastactive_timeformat'], ($post['lastactive'] + $cutofftime));
                $post['last_active_info'] = '<br />' . $lang->sprintf(htmlspecialchars_uni($lang->postbitlastactive_lastactive), $lastactivetime);
            }
        }

        if ($post['lastvisit'] == $post['lastactive'])
        {
            if (empty($mybb->settings['postbitlastactive_timeformat']))
            {
                $mybb->settings['postbitlastactive_timeformat'] = "relative";
            }
            $lastactivetime = my_date($mybb->settings['postbitlastactive_timeformat'], $post['lastactive']);
            $post['last_active_info'] = '<br />' . $lang->sprintf(htmlspecialchars_uni($lang->postbitlastactive_lastactive), $lastactivetime);
        }
    }

    if (!empty($post['last_active_info']))
    {
        $post['user_details'] .= $post['last_active_info'];
    }
}

function postbit_lastactive_settings()
{
    global $lang;
    $lang->load('config_postbit_lastactive');
}

function postbit_lastactive_settings_peekers(&$peekers)
{
    $peekers[] .= 'new Peeker($(".setting_postbitlastactive_enable"), $("#row_setting_postbitlastactive_groupselect, #row_setting_postbitlastactive_timeformat, #row_setting_postbitlastactive_showonlinestatus, #row_setting_postbitlastactive_onlinecutofftime, #row_setting_postbitlastactive_onlinestatus_text, #row_setting_postbitlastactive_onlinestatus_style"), 1, true)';
    $peekers[] .= 'new Peeker($(".setting_postbitlastactive_showonlinestatus"), $("#row_setting_postbitlastactive_onlinecutofftime, #row_setting_postbitlastactive_onlinestatus_text, #row_setting_postbitlastactive_onlinestatus_style"), 1, true)';
}
