<?php
define('IN_PHPBB', true);
require_once('lib/webim.class.php');
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../';

$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
require_once("lib/json.php");
$user->session_begin();
$auth->acl($user->data);
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
$user_data=$user->data;
unset($user);

$user->uid = $user_data['user_id'];
$user->id = $user_data['username'];
$user->nick = $user_data['username'];
$user->pic_url =  im_avatar($user_data);
$user->default_pic_url="./styles/prosilver/theme/images/no_avatar.gif";
$user->group_id=$user_data['group_id'];
$user->show = gp('show') ? gp('show') : "available";
$user->url = "memberlist.php?mode=viewprofile&u=".$user->uid;

$ticket = gp('ticket');
if($ticket){
$ticket = str_replace(array('\\', '//'), '/', $ticket);
}


function im_avatar($user_data){
$user_img=get_user_avatar($user_data['user_avatar'],$user_data['user_avatar_type'],$user_data['user_avatar_width'],$user_data['user_avatar_height']);
return (!empty($user_img) ? $user_img : "./webim/static/images/noavatar_small.gif");
}
//$db->query("SET NAMES utf8");
//$groups = getfriendgroup();
//foreach($groups as $k => $v){
//	$groups[$k] = to_utf8($v);
//}
//
//
//function nick($sp) {
//	global $_IMC;
//	$_nick=(!$_IMC['show_realname']||empty($sp['name'])) ? $sp['username'] : $sp['name'];
//	return $_nick;
//}
//
function ids_array($ids) {
	return ($ids===NULL || $ids==="") ? array() : (is_array($ids) ? array_unique($ids) : array_unique(split(",", $ids)));
}
function ids_except($id, $ids) {
	if(in_array($id, $ids)) {
		array_splice($ids, array_search($id, $ids), 1);
	}
	return $ids;
}

function im_tname($name) {
	//     return "`webim_".$name."`";
	return "webim_".$name;
}


function online_buddy(){
	global  $user, $db,$config,$auth;
	$list = array();
        $update_time = $config['load_online_time'] * 60;

        $sql = $db->sql_build_query('SELECT_DISTINCT', array(
		'SELECT'	=> 'u.user_id, u.user_avatar,u.user_avatar_type,u.user_avatar_width,u.user_avatar_height,u.username, u.username_clean, u.user_colour, MAX(s.session_time) as online_time, MIN(s.session_viewonline) AS viewonline',

		'FROM'		=> array(
			USERS_TABLE		=> 'u',
			ZEBRA_TABLE		=> 'z'
		),

		'LEFT_JOIN'	=> array(
			array(
				'FROM'	=> array(SESSIONS_TABLE => 's'),
				'ON'	=> 's.session_user_id = z.zebra_id'
			)
		),

		'WHERE'		=> 'z.user_id = ' . $user->uid . '
			AND z.friend = 1
			AND u.user_id = z.zebra_id',

		'GROUP_BY'	=> 'z.zebra_id, u.user_id, u.username_clean, u.user_colour, u.username',

		'ORDER_BY'	=> 'u.username_clean ASC',
	));
       $result = $db->sql_query($sql);
	while ($value = $db->sql_fetchrow($result)){
           $on_line= (time() - $update_time < $value['online_time'] && ($value['viewonline'] || $auth->acl_get('u_viewonline')));
           if($on_line){
		$list[] = (object)array(
			"uid" => $value['user_id'],
			"id" => $value['username'],
			"nick" => $value['username'],
			//"group" => $value['group_id'],
			"url" => "memberlist.php?mode=viewprofile&u=".$value['user_id'],
			'default_pic_url' => im_avatar($value),
			"pic_url" =>im_avatar($value)
		);
                }
	}
        $db->sql_freeresult($result);
	return $list;
}
//
//
function complete_status($members){
	if(!empty($members)){
		$num = count($members);
		$ids = array();
		$ob = array();
		for($i = 0; $i < $num; $i++){
			$m = $members[$i];
			$id = $m->uid;
			$ids[] = $id;
			$ob[$id] = $m;
			$m->status = "";
		}
	}
	return $members;
}

//$ids="licangcai,qiukh"
function buddy($ids) {
	global $user, $db;
	$ids = ids_array($ids);
	$ids = ids_except($user->id, $ids);
	if(empty($ids))return array();
	$ids = join("','", $ids);
	$buddies = array();
	$q="SELECT main.user_id, main.username,  main.user_avatar,main.user_avatar_type,main.user_avatar_width,main.user_avatar_height,main.username_clean, f.zebra_id FROM "
		.USERS_TABLE
		." main LEFT OUTER JOIN "
		.ZEBRA_TABLE
		." f ON f.user_id = '$user->uid' AND main.user_id = f.zebra_id WHERE main.username IN ('$ids')";
     
	$result = $db-> sql_query($q);
	while ($value = $db->sql_fetchrow($result)) {
		$id = $value['username'];
		$nick = $value['username'];
		if(empty($value['zebra_id'])) {
			$group = "stranger";
		}else {
                    $group = "friend";
			//$gid = $value['gid'];
			//$group = (empty($gid) || empty($groups[$gid])) ? "friend" : $groups[$gid];
		}
		$buddies[]=(object)array('uid'=>$id,
			'id'=> $nick,
			'nick'=> $nick,
			'pic_url' =>im_avatar($value),
			'status'=>'' ,
			'status_time'=>'',
			'url'=>"memberlist.php?mode=viewprofile&u=".$value['user_id'],
			'group'=> $group,
			'default_pic_url' =>im_avatar($value));
	}
         $db->sql_freeresult($result);
	return $buddies;
}

//
//
//function rooms() {
//	global $_SGLOBAL,$user;
//	$rooms = array();
//	$query = $_SGLOBAL['db']->query("SELECT t.tagid, t.membernum, t.tagname, t.pic
//		FROM ".tname('tagspace')." main
//		LEFT JOIN ".tname('mtag')." t ON t.tagid = main.tagid
//		WHERE main.uid = '$user->uid'");
//	while ($value = $_SGLOBAL['db']->fetch_array($query)) {
//		$tagid = $value['tagid'];
//		$id = $tagid;
//		$tagname = $value['tagname'];
//		$pic = empty($value['pic']) ? 'image/nologo.jpg' : $value['pic'];
//		$rooms[$id]=(object)array('id'=>$id,
//			'nick'=> $tagname,
//			'pic_url'=>$pic,
//			'status'=>'',
//			'status_time'=>'',
//			'all_count' => $value['membernum'],
//			'url'=>'space.php?do=mtag&tagid='.$tagid,
//			'count'=>"");
//	}
//	return $rooms;
//}
//
//
function find_new_message() {
	global $user, $db;
	$id = $user->id;
	$messages = array();
	$result = $db->sql_query("SELECT * FROM "
		.im_tname('histories')
		." WHERE `to`='$id' and send = 0 ORDER BY timestamp DESC LIMIT 100");
	while ($value = $db->sql_fetchrow($result)) {
		array_unshift($messages,array(
			'to'=>$value['to'],
			'nick'=>$value['nick'],
			'from'=>$value['from'],
			'style'=>$value['style'],
			'body'=>$value['body'],
			'timestamp'=>$value['timestamp'],
			'type' =>$value['type']));
	}
         $db->sql_freeresult($result);
	return $messages;
}

function new_message_to_histroy() {
	global $user, $db;
	$id = $user->id;
	$db->sql_query("UPDATE ".im_tname('histories')." SET send = 1 WHERE `to`='$id' AND send = 0");
}

function find_history($ids,$type="unicast") {
	global $user, $db;
	$uname = $user->id;
	$histories = array();
	$ids = ids_array($ids);
	if($ids===NULL)return array();
	for($i=0;$i<count($ids);$i++) {

		$id = $ids[$i];

		$list = array();
		if($type=='multicast') {
			$q="SELECT * FROM ".im_tname('histories')
				. " WHERE (`to`='$id') AND (`type`='multicast') AND send = 1 ORDER BY timestamp DESC LIMIT 30";
			$result = $db->sql_query($q);
			while ($value = $db->sql_fetchrow($result)) {
				array_unshift($list,
					array(
						'to'=>$value['to'],
						'from'=>$value['from'],
					        'style'=>$value['style'],
						'body'=>$value['body'],
						'timestamp'=>$value['timestamp'],
						'type' =>$value['type'],
						'nick'=>$value['nick']));
			}
		}else {
			$q=  "SELECT main.* FROM "
				. im_tname('histories')
				. " main WHERE ((`to`='$id' AND `from`='$uname' AND `fromdel` != 1) or (`from`='$id' AND `to`='$uname' AND `todel` != 1 AND (`send`=1)))  ORDER BY timestamp DESC LIMIT 30";
			$result = $db->sql_query($q);
			while ($value = $db->sql_fetchrow($result)) {
				array_unshift($list,
					array('to'=>$value['to'],
					'nick'=>$value['nick'],
					'from'=>$value['from'],
					'style'=>$value['style'],
					'body'=>$value['body'],
					'type' => $value['type'],
					'timestamp'=>$value['timestamp']));
			}
		}

	}
         $db->sql_freeresult($result);
	return $list;
}
//
function setting() {
	global $db,$user;
	if(!empty($user->uid)) {
		$setting  = $db->sql_fetchrow($db->sql_query("SELECT * FROM ".im_tname('settings')." WHERE uid='$user->uid'"));
		if(empty($setting)) {
			$setting = array('uid'=> $user->uid,'web'=>"");
			$db->sql_query("INSERT INTO ".im_tname('settings')." (uid,web) VALUES ($user->uid,'')");
		}
		$setting = $setting["web"];
	}
	return json_decode(empty($setting) ? "{}" : $setting);
}

/**
* Get user avatar
*
* @param string $avatar Users assigned avatar name
* @param int $avatar_type Type of avatar
* @param string $avatar_width Width of users avatar
* @param string $avatar_height Height of users avatar
* @param string $alt Optional language string for alt tag within image, can be a language key or text
* @param bool $ignore_config Ignores the config-setting, to be still able to view the avatar in the UCP
*
* @return string Avatar image
*/
function get_user_avatar($avatar, $avatar_type, $avatar_width, $avatar_height, $alt = 'USER_AVATAR', $ignore_config = false)
{
	global $user, $config, $phpbb_root_path, $phpEx;

	if (empty($avatar) || !$avatar_type || (!$config['allow_avatar'] && !$ignore_config))
	{
		return '';
	}

	$avatar_img = '';

	switch ($avatar_type)
	{
		case AVATAR_UPLOAD:
			if (!$config['allow_avatar_upload'] && !$ignore_config)
			{
				return '';
			}
			$avatar_img = "download/file.$phpEx?avatar=";
		break;

		case AVATAR_GALLERY:
			if (!$config['allow_avatar_local'] && !$ignore_config)
			{
				return '';
			}
			$avatar_img =  $config['avatar_gallery_path'] . '/';
		break;

		case AVATAR_REMOTE:
			if (!$config['allow_avatar_remote'] && !$ignore_config)
			{
				return '';
			}
		break;
	}

	$avatar_img .= $avatar;
//	return '<img src="' . (str_replace(' ', '%20', $avatar_img)) . '" width="' . $avatar_width . '" height="' . $avatar_height . '" alt="' . ((!empty($user->lang[$alt])) ? $user->lang[$alt] : $alt) . '" />';
return str_replace(' ', '%20', $avatar_img);
}

//function to_utf8($s) {
//	global $_SC;
//	if($_SC['charset'] == 'utf-8') {
///		return $s;
//	} else {
//		return  _iconv($_SC['charset'],'utf-8',$s);
//	}
//}
//
//function from_utf8($s) {
//	global $_SC;
//	if($_SC['charset'] == 'utf-8') {
//		return $s;
//	} else {
//		return  _iconv('utf-8',$_SC['charset'],$s);
//	}
//}
//
//?>
