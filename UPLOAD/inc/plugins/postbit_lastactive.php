<?php
/**
 *  Main plugin file Postbit UserLastActiveTime plugin for MyBB 1.8
 *
 *  Copyright: 2015 - 2019 @Svepu
 *  Last change: 2019-11-28
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}
$plugins->add_hook("admin_config_settings_change",'postbit_lastactive_settings_page');
$plugins->add_hook("admin_page_output_footer",'postbit_lastactive_settings_peeker');
$plugins->add_hook('postbit', 'postbit_lastactive_run');
$plugins->add_hook('postbit_prev', 'postbit_lastactive_run');
$plugins->add_hook('postbit_pm', 'postbit_lastactive_run');
$plugins->add_hook('postbit_announcement', 'postbit_lastactive_run');

function postbit_lastactive_info()
{
    global $plugins_cache, $db, $lang;
    $lang->load("config_postbit_lastactive");
    $info = array(
        "name"          => $db->escape_string($lang->pla_plugin_name),
        "description"   => $db->escape_string($lang->pla_plugin_desc),
        "website"       => "https://github.com/SvePu/MyBB-Postbit-UserLastActiveTime",
        "author"        => "Svepu",
        "authorsite"    => "https://github.com/SvePu",
        "version"       => "1.4",
        "codename"      => "postbitlastactive",
        "compatibility" => "18*"
    );

    $info_desc = '';
    $gid_result = $db->simple_select('settinggroups', 'gid', "name = 'pla_settings'", array('limit' => 1));
    $settings_group = $db->fetch_array($gid_result);
    if(!empty($settings_group['gid']))
    {
        $info_desc .= "<span style=\"font-size: 0.9em;\">(~<a href=\"index.php?module=config-settings&action=change&gid=".$settings_group['gid']."\"> ".htmlspecialchars_uni($lang->pla_settings_title)." </a>~)</span>";
    }

    if(is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['postbit_lastactive'])
    {
        $info_desc .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float: right;" target="_blank" />
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="hosted_button_id" value="VGQ4ZDT8M7WS2" />
<input type="image" src="https://www.paypalobjects.com/webstatic/en_US/btn/btn_donate_pp_142x27.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
<img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1" />
</form>';
    }

    if($info_desc != '')
    {
        $info['description'] = $info_desc.'<br />'.$info['description'];
    }

    return $info;
}

function postbit_lastactive_activate()
{
    global $db, $mybb, $lang;

    $lang->load("config_postbit_lastactive");
    $query_add = $db->simple_select("settinggroups", "COUNT(*) as disporder");
    $disporder = $db->fetch_field($query_add, "disporder");
    $pla_setgroup = array(
        "name"          =>  "pla_settings",
        "title"         =>  $db->escape_string($lang->pla_settings_title),
        "description"   =>  $db->escape_string($lang->pla_settings_title_desc),
        "disporder"     =>  $disporder+1,
        "isdefault"     =>  0
    );
    $gid = $db->insert_query("settinggroups", $pla_setgroup);

    $setting_array = array(
        'pla_enable' => array(
            'title'         => $db->escape_string($lang->pla_enable_title),
            'description'   => $db->escape_string($lang->pla_enable_title_desc),
            'optionscode'   => 'yesno',
            'value'         => 1,
            'disporder'     => 1
        ),
        'pla_groupselect' => array(
            'title'         => $db->escape_string($lang->pla_groupselect_title),
            'description'   => $db->escape_string($lang->pla_groupselect_desc),
            'optionscode'   => 'groupselect',
            'value'         => '3,4,6',
            "disporder"     => 2
        ),
        'pla_timeformat' => array(
            'title'         => $db->escape_string($lang->pla_timeformat_title),
            'description'   => $db->escape_string($lang->pla_timeformat_desc),
            'optionscode'   => 'text',
            'value'         => $db->escape_string($lang->pla_timeformat_default),
            "disporder"     => 3
        ),
        'pla_showonlinestatus' => array(
            'title'         => $db->escape_string($lang->pla_showonlinestatus_title),
            'description'   => $db->escape_string($lang->pla_showonlinestatus_desc),
            'optionscode'   => 'yesno',
            'value'         => 1,
            "disporder"     => 4
        ),
        'pla_onlinecutofftime' => array(
            'title'         => $db->escape_string($lang->pla_onlinecutofftime_title),
            'description'   => $db->escape_string($lang->pla_onlinecutofftime_desc),
            'optionscode'   => 'numeric',
            'value'         => 5,
            "disporder"     => 5
        ),
        'pla_onlinestatus_text' => array(
            'title'         => $db->escape_string($lang->pla_onlinestatus_text_title),
            'description'   => $db->escape_string($lang->pla_onlinestatus_text_desc),
            'optionscode'   => 'text',
            'value'         => 'Online',
            "disporder"     => 6
        ),
        'pla_onlinestatus_style' => array(
            'title'         => $db->escape_string($lang->pla_onlinestatus_style_title),
            'description'   => $db->escape_string($lang->pla_onlinestatus_style_desc),
            'optionscode'   => 'textarea',
            'value'         => 'color:green;\nfont-weight:bold;',
            "disporder"     => 7
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

function postbit_lastactive_deactivate()
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
    global $lang, $mybb;
    if($mybb->settings['pla_enable'] != "1" || $post['uid'] == 0 || $post['usergroup'] == 1 || empty($mybb->settings['pla_groupselect']))
    {
        return;
    }
    if(!is_member($mybb->settings['pla_groupselect']) && $mybb->settings['pla_groupselect'] != "-1")
    {
        return;
    }

    $lang->load("postbit_lastactive");
    $post['last_active_info'] = "";

    if($post['invisible'] != 1 || $mybb->usergroup['canviewwolinvis'] == 1)
    {
        if($mybb->settings['pla_showonlinestatus'] == 1 && $post['lastvisit'] != $post['lastactive'])
        {
            if($mybb->settings['pla_onlinecutofftime'] < 1)
            {
                $mybb->settings['pla_onlinecutofftime'] = 1;
            }
            $cutofftime = $mybb->settings['pla_onlinecutofftime']*60;

            if($post['lastactive'] > TIME_NOW - $cutofftime)
            {
                if(empty($mybb->settings['pla_onlinestatus_text']))
                {
                    $mybb->settings['pla_onlinestatus_text'] = "Online";
                }

                $onlinecssstyle = '';
                if(!empty($mybb->settings['pla_onlinestatus_style']))
                {
                    $mybb->settings['pla_onlinestatus_style'] = preg_replace("%(\r\n)|(\r)%", "", $mybb->settings['pla_onlinestatus_style']);
                    $onlinecssstyle = 'style="' . $mybb->settings['pla_onlinestatus_style'] . '"';
                }
                $post['last_active_info'] = '<br /><span '.$onlinecssstyle.'>' . $mybb->settings['pla_onlinestatus_text'] . '</span>';
            }
            else
            {
                if(empty($mybb->settings['pla_timeformat']))
                {
                    $mybb->settings['pla_timeformat'] = "relative";
                }
                $lastactivetime = my_date($mybb->settings['pla_timeformat'], ($post['lastactive'] + $cutofftime));
                $post['last_active_info'] = '<br />'.$lang->sprintf(htmlspecialchars_uni($lang->pla_lastactive), $lastactivetime);
            }
        }

        if($post['lastvisit'] == $post['lastactive'])
        {
            if(empty($mybb->settings['pla_timeformat']))
            {
                $mybb->settings['pla_timeformat'] = "relative";
            }
            $lastactivetime = my_date($mybb->settings['pla_timeformat'], $post['lastactive']);
            $post['last_active_info'] = '<br />'.$lang->sprintf(htmlspecialchars_uni($lang->pla_lastactive), $lastactivetime);
        }
    }

    $post['user_details'] = $post['user_details'].$post['last_active_info'];
}

function postbit_lastactive_settings_page()
{
    global $db, $mybb, $pla_settings_peeker;
    $result = $db->simple_select('settinggroups', 'gid', "name = 'pla_settings'", array('limit' => 1));
    $pla_group = $db->fetch_array($result);
    $pla_settings_peeker = ($mybb->input["gid"] == $pla_group["gid"]) && ($mybb->request_method != "post");
}

function postbit_lastactive_settings_peeker()
{
    global $pla_settings_peeker;
    if($pla_settings_peeker)
    {
        echo '<script type="text/javascript">
        $(document).ready(function(){
            new Peeker($(".setting_pla_enable"), $("#row_setting_pla_groupselect, #row_setting_pla_timeformat, #row_setting_pla_showonlinestatus, #row_setting_pla_onlinecutofftime, #row_setting_pla_onlinestatus_text, #row_setting_pla_onlinestatus_style"), 1, true),
            new Peeker($(".setting_pla_showonlinestatus"), $("#row_setting_pla_onlinecutofftime, #row_setting_pla_onlinestatus_text, #row_setting_pla_onlinestatus_style"), 1, true)
        });
        </script>';
    }
}
