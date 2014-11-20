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

*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
************************************************************************************/
require '../header.php';

require '../../inc/php/collectExtensionInfos.php';

$genericFolder = '../../../projects/'.$projectName.'/objects/generic/';

require $genericFolder . '__draft.php';

if (!$json = json_decode($model, true)) exit('<h3>draft-model is corrupt!</h3>');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>cms-kit Generic Modeling</title>
<meta charset="utf-8" />
<link href="../../../vendor/cmskit/jquery-ui/themes/<?php echo end($_SESSION[$projectName]['settings']['interface']['theme'])?>/jquery-ui.css" rel="stylesheet" />
<link href="../../templates/default/css/packed_<?php echo end($_SESSION[$projectName]['settings']['interface']['theme'])?>.css" rel="stylesheet" />

<style>
#objectlist
{
	position: absolute;
	top: 80px;
	left: 5px;
	width:200px;
}
#fieldlist
{
	position: absolute;
	top: 0px;
	left: 0px;
	width: 400px;
	border: 1px solid #eee;
	padding: 10px;
}
.ui-icon, .label
{
	display: inline-block;
}
.label
{
	margin-left: 10px;
	width: 70%;
	height: 15px;
}
.ui-icon-trash, .ui-icon-pencil, .ui-icon-copy
{
	float: right;
}
#dialogbody label
{
	display: inline-block;
	width: 120px;
	font-weight: bold;
	border-bottom: 1px solid #ccc;
}
#dialogbody input, #dialogbody textarea, #dialogbody  select
{
	background: #fff;
	width: 400px;
	border: 1px solid #666;
	padding: 5px;
	font: 1.2em/120% Tahoma, Arial, sans-serif; color: navy;
}
iframe
{
	width: 100%;
	height: 490px;
	border: 0px none;
}

.dangerous {
	border: 2px solid #c00;
}
    
</style>


<script src="../../../vendor/cmskit/jquery-ui/jquery.min.js"></script>
<script>$.uiBackCompat = false;</script>
<script src="../../../vendor/cmskit/jquery-ui/jquery-ui.min.js"></script>

<script>if(!window.JSON){document.writeln('<script src="../../../vendor/cmskit/jquery-ui/plugins/json3.min.js"><\/script>')}</script>

<script type="text/javascript">
/* <![CDATA[ */
var disallowedFieldNames = [' ', '_', 'id','__TEMPLATE__','__TEMPLATE_SELECT__'],
	actmodel = false,
	actfields = [],
<?php
echo "
	model = ".json_encode($json).",
	project = '".$projectName."',
	wizards = [];
";

// available Wizards (backend/inc/php/collectExtensionInfos.php)
$embeds = collectExtensionInfos($projectName);
// print JS for available Wizards
foreach($embeds['w'] as $k => $v)
{
	echo  "	wizards['$k'] = {" . implode(',', $v) . "}\n";
}


// these Types makes no Sense in a JSON-Model
$forbiddenTypes = array(
							'BLOB',
							'MODEL',
						);
// load and decode datatypes from JS-Json
$datatypes = json_decode(file_get_contents('../../inc/js/rules/datatypes.json'), true);

// create the type-selector
echo 'var typeSelect = \'<select onchange="checkTypeSelect(this)" name="type">';
foreach($datatypes as $k => $v)
{
	if(!in_array($k, $forbiddenTypes))
	{
		echo '<option value="' . $k . '">' . addslashes(L($k)) . '</option>';
	}
	foreach($v['default'] as $dk=>$dv)
	{
		$ddefaultLabel[] = $dk;
	}
}
echo '</select>\';';

// print JS for translated labels
echo '
var ddefault = [];
';
$ddefaultLabel = array_unique($ddefaultLabel);
foreach($ddefaultLabel as $dl)
{
	if (isset($v['default'][$dl])) echo "ddefault['$dl'] = ['".L($dl)."','".$v['default'][$dl]."'];\n";
}

?>

// Array Remove - By John Resig (MIT Licensed)
Array.prototype.remove = function(from, to) {
	var rest = this.slice((to || from) + 1 || this.length);
	this.length = from < 0 ? this.length + from : from;
	return this.push.apply(this, rest);
};

/**
* onload
*
*/
$(function()
{
	
	$('body').on({
		ajaxStart: function() {
			$(this).addClass('loading');
		},
		ajaxStop: function() {
			$(this).removeClass('loading');
		}
	});
	
	// object list
	$('#objectlist').on('click', '.label', function()
	{
		$('#objectlist li').removeClass('ui-selected');
		$(this).parent().addClass('ui-selected');
		var n = $(this).parent().data('name');
		/*var a = {};
			a.name = n;
			a = {};
			a.field = [];*/
		//model.object.push(a);
		showModel(n);
	});
	
	// File delete
	$('#objectlist').on('click', '.ui-icon-trash', function()
	{
		var name = $(this).parent().data('name');
		var q = confirm('<?php echo L('delete')?> '+name+'?');
		if(q)
		{
			$.get('json_io.php',
			{
				action : 'delete',
				project : project,
				file : name
			}, 
			function (data)
			{
				alert(data);
				location.reload();
			});
			//$(this).parent().remove();
		}
	});
	
	// File copy
	$('#objectlist').on('click', '.ui-icon-copy', function()
	{
		var name = $(this).parent().data('name');
		var nn = prompt('<?php echo L('enter_new_Name')?>', '');
		if(nn)
		{
			$.get('json_io.php',
			{
				action : 'dup',
				project : project,
				file : name,
				newfile : nn
			},
			function (data)
			{
				alert(data);
				location.reload()
			});
			
		}
	});
	
	// add object
	$('#addModelButton').on('click', function()
	{
		var n = prompt('<?php echo L('enter_new_Name')?>', '');
		
		if(n)
		{
			n = n.replace(' ','_').replace(/[^\d\w]/g, '').toLowerCase();
			
			$.get('json_io.php',
			{
				action : 'add',
				project : project,
				file : n
			},
			function(data)
			{
				if(data == 'ok')
				{
					//$('#objectlist').append($('<li class="ui-state-default ui-selectee" data-name="'+n+'"><span class="ui-icon ui-icon-trash"></span><span class="label">'+n+'</span></li>'));
					location.reload()
				}
			});
		}
	});
	
	// open a help-window pointing to: admin/generic_modeling/doc/LANG/.generic_modeling.md
	$('#getHelpButton').on('click', function()
	{
		$('#dialogbody').html('<iframe src="../package_manager/showDoc.php?file=../../admin/generic_modeling/doc/<?php echo $lang?>/.generic_modeling.md"></iframe>');
		$('#dialog_SaveButton').hide();
		$('#dialog').dialog('open');
		
	});
	
	// ???
	$('#gotoRestore').on('click', function()
	{
		window.location = 'restore.php?project='+project;
	});
	
	
	
	
	
	// object selector list
	$('#objectselectorlist').on('click', '.label', function()
	{
		$('#objectselectorlist li').removeClass('ui-selected');
		$(this).parent().addClass('ui-selected');
		var n = $(this).parent().data('name');
		
		var html = '<iframe style="position:absolute;top:60px;left:220px;width:600px;height:600px" src="inc/manage_selects.php?project='+project+'&file='+n+'&action=edit"></iframe>';
		$('#fieldlist').html(html);
		
		//$('#dialogbody').html();
		//$('#dialog_SaveButton').hide();
		//$('#dialog').dialog('open');
		
	});
	
	// File delete
	$('#objectselectorlist').on('click', '.ui-icon-trash', function()
	{
		var n = $(this).parent().data('name');
		var q = confirm('<?php echo L('delete')?> '+n+'?');
		if(q)
		{
			$.get('inc/manage_selects.php?project='+project+'&file='+n+'&action=delete', function(data)
			{
				alert(data);
				location.reload();
			});
		}
	});
	$('#addModelSelectButton').on('click', function()
	{
		var n = prompt('<?php echo L('enter_new_Name')?>', '');
		if(n)
		{
			n = n.replace(' ','_').replace(/[^\d\w]/g, '').toLowerCase();
			
			$.get('inc/manage_selects.php?project='+project+'&file='+n+'&action=create', function(data)
			{
				alert(data);
				location.reload();
			});
		}
	});
	
	
	
	
	
	$('#dialog').dialog(
	{
		autoOpen: false,
		modal: true,
		width: 600,
		height: 650,
		close: function() {
			$('#dialogbody').html('');
			$('#dialog_SaveButton').show();
		},
		buttons: [
			{
				text: '<?php echo L('save')?>',
				id: 'dialog_SaveButton',
				click: function()
				{
					serializeModel();
					$(this).dialog( 'close' );
				}
			},
			{
				text: '<?php echo L('close')?>',
				click: function()
				{
					$(this).dialog( 'close' );
				}
			}
		]
	});
	
	
	$( "#colLeft" ).tabs();
	
});// ready end

/**
* save/export the model to the server
*
*/
function saveModel(action)
{
	$.post('json_io.php?project='+project+'&action='+action+'&file='+actmodel,
	{
		json : JSON.stringify(model)
	},
	function (data)
	{
		alert(data);
		afterSaveModel(actmodel);
	});
};

// dummy function to overload from outside
function afterSaveModel(n){}
/**
*
*
*/
function serializeModel()
{
	// serialize and store the fields temporarily
	var arr = $('#editform').serializeArray();
	var name2save = $('#editform').data('fieldname');
	var t = {};
	for (var i=0,j=arr.length; i<j; ++i)
	{
		t[arr[i]['name']] = esc(arr[i]['value']);
	}
	
	// loop the fields to find the right one
	for (var ii=0,jj=model.objects[actmodel].length; ii<jj; ++ii)
	{
		if (model.objects[actmodel][ii].name == name2save)
		{
			model.objects[actmodel][ii] = t;
		}
	}
	
	//alert(JSON.stringify(model, null, '\t'));
};

/**
* show the model as a editable field list
*
*/
function showModel(name)
{
	// store the actual modelname into a global variable
	actmodel = name;
	
	// create the list header
	html = 	'<div id="colMid">'
			
			+'<span  style="float:right">'
			+'<button id="saveFieldButton" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" '
			+'role="button" aria-disabled="false"><span class="ui-button-icon-primary ui-icon ui-icon-disk"></span><span class="ui-button-text"><?php echo L('save');?></span>'
			+'</button> '
			+'<button id="exportObjectsButton" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" '
			+'role="button" aria-disabled="false"><span class="ui-button-icon-primary ui-icon ui-icon-gear"></span><span class="ui-button-text"><?php echo L('export');?></span>'
			+'</button> '
			//'<input type="checkbox" title="do not perform any DB-Update" id="no_db_update" />' +
			+'</span>'
			
			+'<button id="showJsonButton" style="float:right" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" ' +
			+'role="button" aria-disabled="false"><span class="ui-button-icon-primary ui-icon ui-icon-disk"></span><span class="ui-button-text"><?php echo L('show_code');?></span>'
			+'</button> '
			
			+'<button id="addFieldButton" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" ' 
			+'role="button" aria-disabled="false"><span class="ui-button-icon-primary ui-icon ui-icon-plus"></span><span class="ui-button-text"><?php echo L('new_field');?></span>'
			+'</button>'
			
			+'<ul id="fieldUL" class="ilist rlist">';
	
	// LI-template
	var lstr = '<li class="ui-state-default ui-selectee" data-name="XX" id="col_XX"><span title="<?php echo L('delete_field')?>" class="ui-icon ui-icon-trash"></span><span title="<?php echo L('edit_field_properties')?>" class="ui-icon ui-icon-pencil"></span><span title="<?php echo L('drag_to_sort')?>" class="ui-icon ui-icon-arrowthick-2-n-s"></span><span class="label">XX</span></li>';
	
	
	for(var i=0,j=model.objects[name].length; i<j; ++i)
	{
		var str = lstr.replace(/XX/g, model.objects[name][i].name);
		if (model.objects[name][i].name == '__TEMPLATE__')
		{
			str = str.replace('icon-trash"', 'icon-trash" style="display:none"')
		}
		html += str;
		// store the fields into a global named hash
		actfields.push(model.objects[name][i].name);
	}
	
	html += '</ul></div>';
	
	
	$('#fieldlist').html(html);
	
	
	// add a Field
	$('#addFieldButton').on('click', function()
	{
		var n = prompt('<?php echo L('enter_field_name')?>', '');
		if (n)
		{
			
			n = n.replace(' ','_').replace(/[^\d\w]/g, '');
			
			
			if ($.inArray(n, actfields) != -1)
			{
				alert('<?php echo L('field_name_already_exists')?>!');
				return;
			}
			
			if ($.inArray(n, disallowedFieldNames) != -1)
			{
				alert('<?php echo L('field_name_is_not_allowed')?>!');
				return;
			}
			
			
			var f = {};
				f.name = n;
				f.type = 'INTEGER';
				f.lang = '';
				f.filter = '';
				f.add = '';
				f.default = '';
				f.tags = '';
				f.comment = '';
			
			
			
			// add the new field to...
			actfields.push(f); // the array
			model.objects[name].push(f); // and the model
			
			
			// add a list item to the field list and refresh the ui-sortable
			var li = $(lstr.replace(/XX/g, n));
			$('#fieldUL').append(li);
			$('#fieldUL').sortable('refresh');
		}
	});
	
	$('#saveFieldButton').on('click', function()
	{
		saveModel('save')
	});
	
	$('#exportObjectsButton').on('click', function()
	{
		var q0 = confirm('<?php echo L('first_open_database_management_to_create_a_full_backup')?>?');
		if (q0)
		{
			window.open('../db_admin/index.php?project='+project, 'DB-Admin');
		}
		else
		{
			var q1 = confirm('<?php echo L('export_model')?>?');
			if (q1)
			{
				saveModel('export');
			}
		}
	});
	
	$('#showJsonButton').on('click', function()
	{
		var str = JSON.stringify(model, null, '\t');
		
		var html  = '<span style="float:right">' +
					+'<button id="saveOnlyJson" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary ui-state-error" '
					+'role="button" aria-disabled="false"><span class="ui-button-icon-primary ui-icon ui-icon-disk"></span><span class="ui-button-text"><?php echo L('save_Json');?> (<?php echo L('no_DB_Update');?>)</span>'
					+'</button>'
					+'<button id="replaceInDbModels" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary ui-state-error" '
					+'role="button" aria-disabled="false"><span class="ui-button-icon-primary ui-icon ui-icon-scissors"></span><span class="ui-button-text"><?php echo L('replace_DB_Model_String');?></span>'
					+'</button>'
					+'</span>' 
					
					+'<button id="loadStrButton" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" ' 
					+'role="button" aria-disabled="false"><span class="ui-button-icon-primary ui-icon ui-icon-arrowreturnthick-1-s"></span><span class="ui-button-text"><?php echo L('load_Json');?></span>'
					+'</button>'
					
					+'<p><textarea id="jsonField" style="width:95%;height:500px">'+str+'</textarea></p>'
					
					//'<button id="clearBackups" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary ui-state-error" ' +
					//'role="button" aria-disabled="false"><span class="ui-button-icon-primary ui-icon ui-icon-trash"></span><span class="ui-button-text"><?php echo L('clear_Backups');?></span>'+
					//'</button>'+
					+'';
		
		$('#dialogbody').html(html);
		
		
		
		$('#dialog_SaveButton').hide();
		$('#dialog').dialog('open');
	});
	
	
	// delete a Field
	$('#fieldlist').on('click', '.ui-icon-trash', function()
	{
		
		var name = $(this).parent().data('name');
		var q = confirm('<?php echo L('delete_%s')?>'.replace(/\%s/,name)+'?');
		if (q)
		{
			//
			//delete(model[actmodel]);
			for(var i=0,j=model.objects[actmodel].length; i<j; ++i)
			{
				if (model.objects[actmodel][i].name == name)
				{
					model.objects[actmodel].remove(i);
					$('#col_'+name).remove();
				}
			}
		}
	});
	
	// edit a Field
	$('#fieldlist').on('click', '.ui-icon-pencil', function()
	{
		editField($(this).parent().data('name'));
	});
	
	// resort the Fields
	$('#fieldUL').sortable(
	{
		handle: '.ui-icon-arrowthick-2-n-s',
		update: function(event, ui)
		{
			var sortedIDs = $('#fieldUL').sortable("toArray");
			var tmp = [];
			for(var i=0, l=sortedIDs.length; i<l; ++i)
			{
				var n = sortedIDs[i].substr(4);
				
				for(var ii=0,jj=model.objects[actmodel].length; ii<jj; ++ii)
				{
					if (model.objects[actmodel][ii].name == n)
					{
						tmp.push(model.objects[actmodel][ii])
					}
				}
			}
			model.objects[actmodel] = tmp;
			saveModel('save');
		}
	});
	
	afterShowModel(name)
};

// dummy function to overload from outside
function afterShowModel(n){}

//php.js
function esc(str)
{
	str = (str + '').toString();
	// Tilde should be allowed unescaped in future versions of PHP, but if you want to reflect current
	// PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
	return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
	replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
}
//php.js
function unesc(str)
{
	return decodeURIComponent((str + '').replace(/\+/g, '%20'));
}

// do we neet this anymore???
function toArr(str)
{
	var arr = str.split("\n"), out = {};
	for (var i=0,j=arr.length; i<j; ++i)
	{
		var arr1 = arr[i].split(':');
		if(arr1[0])
		{
			out[arr1.shift()] = $.trim(arr1.join(':'));
		}
	}
	return out;
}

function checkTypeSelect(el)
{
	var t = el.value;
	$('#addField').val('');
	if(wizards[t])
	{
		var s = '<select><option value=""><?php echo L('select');?></option>';
		for(w in wizards[t])
		{
			s += '<option value="'+wizards[t][w][0]+'">'+wizards[t][w][1]+'</option>';
		}
		s += '</select>';
		$('#addSelect').html(s);
	}
	else
	{
		$('#addSelect').html('');
	}
}

// draw the Editing-Window
/**
*
*
*/
function editField(name)
{
	var m = actmodel, // model name
		t = '',
		html  = '<form data-fieldname="'+name+'" id="editform">';
		html += '<h3>'+( '<?php echo L('edit_field_%s');?>'.replace(/\%s/, '<em>'+name+'</em>') )+'</h3>';
		
		
		// loop the fields to find the right one
		for (var ii=0,jj=model.objects[actmodel].length; ii<jj; ++ii)
		{
			if (model.objects[actmodel][ii].name == name)
			{
				var myField = model.objects[actmodel][ii];
			}
		}
		
		// ??
		for (i in m['lang'])
		{
			var s = m['lang'][i];
			if (typeof(s)=='object')
			{
				s = (s['accordionhead']?s['accordionhead']+' -- ':'') + 
					(s['tabhead']?s['tabhead']+' || ':'') + 
					(s['label']?s['label']:'') +
					(s['placeholder']?' ['+s['placeholder']+'] ':'') + 
					(s['tooltip']?' ('+s['tooltip']+')':'') +
					(s['doc']?' <'+s['doc']+'> ':'')
			}
			t += i+':'+s+"\n";
		}
		
		html += '<input type="hidden" name="name" value="'+name+'" />';
		if(myField.value) html += '<input type="hidden" name="value" value="'+myField.value+'" />';
		
		html += '<p><label><?php echo L('language_labels');?></label><textarea name="lang">'+ unesc(myField.lang) + '</textarea></p>';	
		
		
		t = new RegExp('value="'+myField.type+'"');
		html += '<p><label><?php echo L('datatype');?></label>'+typeSelect.replace(t, 'value="'+myField.type+'" selected="selected"')+'</p>';
		
		html += '<p><span style="margin-left:120px" id="addSelect"></span>';
		
		html += '<p><label><?php echo L('addition');?></label><textarea id="addField" name="add">'+unesc(myField.add)+'</textarea></p>';
		
		html += '<p><label><?php echo L('default_value');?></label><input type="text" name="default" value="'+(myField.default?unesc(myField.default):'')+'" /></p>';
		
		html += '<p><label><?php echo L('tags');?></label><textarea name="tags">'+(myField.tags?unesc(myField.tags):'')+'</textarea></p>';
		
		html += '<p><label><?php echo L('comment');?></label><textarea name="comment">'+(myField.comment?unesc(myField.comment):'')+'</textarea></p>';
		
		html += '</form>';
		
	$('#dialogbody').html(html);
	$('#addSelect').on('change', 'select', function()
	{
		$('#addField').val( $.trim($('#addField').val() + "\n" + $(this).val()) );
	});
	$('#dialog_SaveButton').show();
	$('#dialog').dialog('open');
}



/* ]]> */
</script>
</head>
<body>

<!-- left menu/list area -->
<div id="colLeft">
	<ul>
		<li><a href="#tab-1"><?php echo L('Models')?></a></li>
		<li><a href="#tab-2"><?php echo L('Model_selectors')?></a></li>
	</ul>
	<div id="tab-1">
	
	<span style="float:right">
	<button
		id="gotoRestore"
		title="<?php echo L('backups')?>"
		class="ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only" 
		role="button" 
		aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-clock"></span>
			<span class="ui-button-text"><?php echo L('restore_from_Backup')?></span>
	</button>
	<button
		id="getHelpButton"
		title="<?php echo L('get_help')?>"
		class="ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only" 
		role="button" 
		aria-disabled="false">
		<span class="ui-button-icon-primary ui-icon ui-icon-help"></span>
		<span class="ui-button-text"><?php echo L('get_help')?></span>
	</button>
	
	</span>
	
	<button
		id="addModelButton"
		class="ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only" 
		role="button" 
		aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-plus"></span>
			<span class="ui-button-text"><?php echo L('new_model')?></span>
	</button>
	
	<ul id="objectlist" style="clear:both" class="ilist rlist">
	<?php
	// draw object list
	foreach($json['objects'] as $n=>$o)
	{
		//$n = $o['name'];
		echo '<li class="ui-state-default ui-selectee" data-name="'.$n.'">
				<span title="'.L('delete_model').'" class="ui-icon ui-icon-trash"></span>
				<span title="'.L('duplicate_model').'" class="ui-icon ui-icon-copy"></span>
				<span class="label">'.$n.'</span>
			 </li>';
	}
	?>
	</ul>
	</div>
	
	<div id="tab-2">
		
		
	<button
		id="addModelSelectButton"
		class="ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only" 
		role="button" 
		aria-disabled="false">
			<span class="ui-button-icon-primary ui-icon ui-icon-plus"></span>
			<span class="ui-button-text"><?php echo L('new_model_select')?></span>
	</button>
	<ul id="objectselectorlist" style="clear:both" class="ilist rlist">
		
	<?php
		foreach(glob($genericFolder.'*.json') as $loaderJson) {
			
			$name = basename($loaderJson);
			$name = substr($name, 0, strlen($name)-5);
			
			//echo '<option value="'.$name.'">'.L('edit').' '.$name.'</option>';
			echo '<li class="ui-state-default ui-selectee" data-name="'.$name.'">
				<span title="'.L('delete_model_select').'" class="ui-icon ui-icon-trash"></span>
				<span class="label">'.$name.'</span>
			 </li>';
		}
	?>
	</ul>
	</div>
</div>

<!-- object fieldlist area -->
<div id="fieldlist"></div>

<!-- dialog div -->
<div id="dialog"><div id="dialogbody"></div></div>

<!-- waiter div -->
<div class="wait"></div>

<!-- allow project-specific adaptions via a loadable JS -->
<script src="../../../projects/<?php echo $projectName?>/extensions/default/generic_modeling.js"></script>
</body>
</html>
