<?php
/********************************************************************************
*  Copyright notice
*
*  (c) 2014 Christoph Taubmann (info@cms-kit.org)
*  All rights reserved
*
*  This script is part of cms-kit Framework. 
*  This is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License Version 3 as published by
*  the Free Software Foundation, or (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/licenses/gpl.html
*  A copy is found in the textfile GPL.txt and important notices to other licenses
*  can be found found in LICENSES.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
************************************************************************************/
require '../header.php';

$me = 'restore.php';
$bpath = $projectPath . 'generic/backup/';
$ts = time();

if(isset($_GET['showfile']) && $content = file_get_contents($bpath . preg_replace('/[^a-z0-9-]/', '', $_GET['showfile']) . '.php'))//
{
	exit(substr($content,13));
}

// clear Backups
if(isset($_GET['clear']) && isset($_GET['TIMESTAMP']))
{
	$list = glob( $bpath . '*.php' );
	foreach($list as $p)
	{
		$arr = explode('-', substr(basename($p), 0, -4));
		if (intval($arr[0]) <= intval($_GET['TIMESTAMP']))
		{
			unlink($p);
			//echo $arr[0];
		}
		
	}
	unset($_GET['TIMESTAMP']);
}




// TIMESTAMP-ACTION-MODELNAME-USERID-OBJECTNAME-FIELDNAME-OBJECTID.php
$lookfor = array('TIMESTAMP','ACTION','MODELNAME','USERID','OBJECTNAME','FIELDNAME','OBJECTID');
$replace = array();
$code    = array();
$url = array('project'=>$projectName);
foreach($lookfor as $v)
{
	$replace[] = $url[$v] = (!empty($_GET[$v]) ? $_GET[$v] : '*');
	$code[$v] = array();
	
}
$sortby = $url['sb'] = $_GET['sb'] = (isset($_GET['sb']) ? intval($_GET['sb']) : 0);
$offset = $url['os'] = $_GET['os'] = (isset($_GET['os']) ? intval($_GET['os']) : 0);
$filelinks =  100;

$files = array();
$list = glob( $bpath . implode('-', $replace) . '.php' );
$amount = count($list);

foreach($list as $p)
{
	
	$name = substr(basename($p), 0, -4);
	$arr  = explode('-', $name);
	
	$files[] = '<span class="x'.$arr[$sortby].'" onclick="show(this)" title="'.date('d.m.Y H:i:s', intval($arr[0])).' / '.$name.'" alt="'.$name.'">'.$arr[6].'</span>';
	
	foreach($lookfor as $k => $v)
	{
		$code[$v][] = $arr[$k];
	}
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Re-Import from Backup(s)</title>
<meta charset="utf-8" />
<link href="../../../vendor/cmskit/jquery-ui/themes/<?php echo end($_SESSION[$projectName]['config']['theme'])?>/jquery-ui.css" rel="stylesheet" />
<link href="../../inc/css/<?php echo end($_SESSION[$projectName]['config']['theme'])?>/style.css" rel="stylesheet" />
<style>
body
{
	font-size: 1em;
}
#header
{
	border-bottom: 1px solid #000;
}
#header div, #header form
{
	margin-bottom: 10px;
}
#filelist
{
	position: absolute;
	top: 220px;
	width: 400px;
}
#filelist span
{
	cursor: pointer;
	float:left;
	margin: 5px;
	padding: 5px;
}
#working
{
	position: absolute;
	top: 220px;
	left: 440px
}
#clearBackupForm
{
	position: absolute;
	right: 5px;
	top: 0px;
}
label
{
	display:inline-block;
	width: 160px;
}
a, a:visited
{
	color: #000;
	text-decoration: none;
}

a.active
{
	font-weight: bold;
}
</style>
<script src="../../../vendor/cmskit/jquery-ui/jquery.min.js"></script>
</head>
<body>


<?php

////////////////////////////////////////////////////////////
$restoredMsg = '';
if (isset($_POST['f_i_l_e_n_a_m_e']))
{
	$restoredMsg = '<h3>'.L('restored_IDs').':</h3>';
	$filename = $_POST['f_i_l_e_n_a_m_e'];
	$arr = explode('-', $filename);
	unset($_POST['f_i_l_e_n_a_m_e']);
	$props = array();
	foreach ($lookfor as $k => $v)
	{
		$props[$v] = $arr[$k];
	}
	
	//
	include $projectPath . 'class.' . $props['OBJECTNAME'] . '.php';
	$obj = new $props['OBJECTNAME']();
	
	
	if (isset($_POST['i_m_p_o_r_t__a_l_l']))// import only from/to one entry (detected by ID)
	{
		array_pop($arr);
		array_push($arr, '*');
		unset($_POST['i_m_p_o_r_t__a_l_l']);
	}
	
	
	$list = glob($bpath . implode('-', $arr) . '.php');
	// now loop the Backups
	foreach($list as $bf)
	{
		// 'TIMESTAMP','ACTION','MODELNAME','USERID','OBJECTNAME','FIELDNAME','OBJECTID'
		$a = explode('-', substr(basename($bf),0,-4));
		$entry = $obj->Get($a[6]);
		
		// create a Backup of the the Entry first
		$backup = $bpath . $ts . '-restore-' . $a[2] . '-' . $_SESSION[$projectName]['special']['user']['id'] . '-' . $a[4] . '-' . $a[5] . '-' . $a[6] . '.php';
		file_put_contents( $backup, '<?php exit;?>'."\n".$entry->$props['FIELDNAME'] );
		chmod($backup, 0777);
		
		$bjson = json_decode(substr(file_get_contents($bf),13), true);
		$ejson = json_decode($entry->$props['FIELDNAME'], true);
		
		if($bjson && $ejson)
		{
			
			foreach($_POST as $k => $v)
			{
				$ejson[$k]['value'] = $bjson[$k]['value'];
			}
			
			$entry->$props['FIELDNAME'] = json_encode($ejson);
			//echo $entry->$props['FIELDNAME'];
			$entry->Save();
			
			$restoredMsg .= $a[6] . ' ';
		}
	}
	
}
////////////////////////////////////////////////////////////

echo '<a href="index.php?project='.$projectName.'">&lArr; '.L('back').'</a>
<h4>'. $amount . ' '.L('Backup_Files').'</h4>
<div id="header">
';

// Filter-Form
echo '<form method="get" action="'.$me.'">
<input type="hidden" name="project" value="'.$projectName.'" />

<label>'.L('Filter_by').':</label>
';

foreach($code as $k => $v)
{
	$v = array_unique($v);
	$o  = '<select title="'.$k.'" name="'.$k.'"><option value="">'.$k.'</option>';
	foreach($v as $e)
	{
		$o .= '<option'.((!empty($_GET[$k])&&$_GET[$k]==$e)?' selected="selected"':'').' value="'.$e.'">';
		$o .= ($k=='TIMESTAMP') ? date('d.m.Y H:i:s', intval($e)) : $e;
		$o .= '</option>';
	}
	$o .= '</select>';
	if($k=='TIMESTAMP'){ $cleardrop = $o; }
	echo $o;
}
echo ' <input type="submit" value="'.L('Filter_List').'" />
</form>';



$query = http_build_query($url);

// Pagination
echo '<div><label>'.L('Offset').':</label>';
for($i=0; $i<$amount; $i+=$filelinks)
{
	echo '<a '.($_GET['os']==$i?'class="active" ':'').'href="'.$me.'?'.$query.'&os='.$i.'">'.$i.'</a> / ';
}
echo '</div>';

// Sort By
echo '<div><label>'.L('Sort_by').':</label>';
foreach($lookfor as $k => $v)
{
	echo '<a '.($_GET['sb']==$k?'class="active" ':'').'href="'.$me.'?'.$query.'&sb='.$k.'">'.$v.'</a> ';
}
echo '</div>
</div>

';

// File-List
sort($files);

echo '<div id="filelist">';
echo implode('', array_slice($files, $offset, $filelinks));
echo '</div>';



echo '<form id="working" method="post" action="'.$me.'?'.$query.'">'.$restoredMsg.'</form>';

echo '<form id="clearBackupForm" method="get" onsubmit="return askClearAction()">
<input type="hidden" name="project" value="'.$projectName.'" />
<input type="hidden" name="clear" value="1" />
<input type="submit" value="'.L('clear_Backups_up_to').'" />'.
$cleardrop.
'</form>';

?>

<script>
	
function show(el)
{
	var filename = el.getAttribute('alt');
	$.get('<?php echo $me?>',
	{
		project: '<?php echo $projectName?>',
		showfile: filename
	},
	function(data)
	{
		var json = JSON.parse(data),
			html = '<input type="hidden" name="f_i_l_e_n_a_m_e" value="'+filename+'" />';
			
		for(e in json)
		{
			if(e != 'MODEL') html += '<p><input type="checkbox" name="'+e+'" /> <label>'+e+'</label> <textarea readonly="readonly" cols="30" rows="1">'+json[e]['value']+'</textarea></p>';
		}
		html += '<p><input type="submit" value="<?php echo L('Import')?>" /> <input type="checkbox" name="i_m_p_o_r_t__a_l_l" /> <b>&lArr; <?php echo L('import_selected_Fields_from_all_Backups_of_this_Session')?>!</b></p>';
		$('#working').html(html);
	});
}

function askClearAction()
{
	return confirm('<?php echo L('really_delete_Backups')?>?');
}

</script>

</body>
</html>
