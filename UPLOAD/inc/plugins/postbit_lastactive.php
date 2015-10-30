<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook('postbit', 'postbit_lastactive_run');

function postbit_lastactive_info()
{
    return array(
        "name"          => "User Last Active Time in Postbit",
        "description"   => "Simple MyBB 1.8 plugin to show the last active time of user in postbit details",
        "website"       => "https://github.com/SvePu/MyBB-Postbit-Lastactive",
        "author"        => "Svepu",
        "authorsite"    => "https://github.com/SvePu",
        "version"       => "0.9 BETA",
        "codename"      => "postbitlastactive",
        "compatibility" => "*"
    );
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
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'lastactive\']}')."(\r?)\n#", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'lastactive\']}')."(\r?)\n#", '', 0);
}

function postbit_lastactive_run(&$post)
{	global $mybb;
	if($post['lastvisit'] == $post['lastactive'] && $mybb->user['uid'] != 0 && is_member('3,4,6'))
	{
		$post['lastactive'] = "<br />Last active: ".my_date('relative', $post['lastactive']);
	}
	else
	{
		$post['lastactive'] = '';
	}	
}