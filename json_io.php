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

require '../database_modeling/inc/process_includes.php';
require '../database_modeling/inc/process_label.php';

$action = preg_replace('/\W/', '', $_GET['action']);

// define some paths
$backuppath = $projectPath . 'generic/backup/';
$objectName = preg_replace('/\W/', '', $_GET['file']);
$filepath = $projectPath . 'generic/' . $objectName . '.php';
$draftpath = $projectPath . 'generic/__draft.php';

// check if everything is writable
if(!is_writable($projectPath . 'generic'))	exit('ERROR: "objects/generic/" is not writable!');
if(!is_writable($backuppath))			exit('ERROR: "objects/generic/backup/" is not writable!');
if(!is_writable($draftpath))			exit('ERROR: "objects/generic/__draft.php" is not writable!');

// include the main model
require $draftpath;
if (!$json = json_decode($model, true)) exit('draft-model is corrupt!');

// load the datatypes (do we need this??)
$datatypes = json_decode(file_get_contents('../../inc/js/rules/datatypes.json'), true);// load Datatypes

/**
 * 
 * 
 */
function saveDraft()
{

$d1 = '<?php
$model = <<<EOD
';
$d2 = '
EOD;
?>
';
	global $draftpath, $json;
	file_put_contents($draftpath, $d1.indentJson(json_encode($json)).$d2);
}

// 
switch ($action)
{
	
	case 'add':
		if (!file_exists($filepath))
		{
			
			$json['objects'][$objectName] = array	(array(
																			'name' => '__TEMPLATE__',
																			'type' => 'VARCHAR',
																			'tpl' => 'string',
																			'lang' => '',
																			'add' => 'readonly:true',
																			'value' => $objectName,
																		
													));
			
			file_put_contents($filepath, "<?php\n\$genericModel['".$objectName."'] = json_decode('".json_encode($json['objects'][$objectName])."', true);\n?>\n");
			chmod($filepath, 0776);
			
			
			saveDraft();
			echo 'ok';
		}
		else
		{
			echo L('object_already_exists');
		}
	break;
	case 'delete':
		if (file_exists($filepath))
		{
			unlink($filepath);
			unset($json['objects'][$objectName]);
			saveDraft();
			echo L('object_deleted');
		}
	break;
	case 'dup':
		if (file_exists($filepath))
		{
			$newObjectName = preg_replace('/\W/', '', $_GET['newfile']);
			$newfilepath = dirname($filepath) . '/' . $newObjectName . '.php';
			copy($filepath, $newfilepath);
			chmod($newfilepath, 0777);
			
			$json['objects'][$newObjectName] = $json['objects'][$objectName];
			saveDraft();
			
			echo L('object_duplicated');
		}
	break;
	case 'save':
		if ($json = json_decode($_POST['json'], true)) saveDraft();
		echo 'ok';
	break;
	case 'export':
	
		if ($arr = json_decode($_POST['json'], true))
		{
			$json = $arr;
			saveDraft();
			
			// we have to check if the file is writable
			if(!is_writable($filepath)) exit(L('ERROR').': "'.$objectName.'.php" '.L('is_not_writable').'!');
			
			// loop the fields and process field-properties
			$myFields = array();
           // print_r($json['objects'][$objectName]);
			
			foreach ($json['objects'][$objectName] as $f)
			{
				$tmp = array();
				// catch the datatype
				$tmp['type'] = $f['type'];
				$tmp['tpl'] = $datatypes[$f['type']]['tpl'];
				
				// process strings as arrays
				if (@$b = text2array($f['add']))
				{
					$tmp['add'] = $b;
				}
				if (@$b = text2array($f['lang']))
				{
					$tmp['lang'] = processLabel($b);
				}
				if (@$b = text2array($f['tags'], false, true))
				{
					$tmp['tags'] = $b;
				}
				
				// collect simple string
				if (isset($f['default']))
				{
					$tmp['default'] = $f['default'];
				}
				
				
				$myFields[$f['name']] = $tmp;
				
			}// foreach END

            $myFields['__TEMPLATE__']['default'] = $objectName;

			// FIRST load the old model
			include_once $filepath;
			//print_r($myFields);

			// THEN write the new model back
			if(file_put_contents($filepath, "<?php\n\$genericModel['".$objectName."'] = json_decode('".json_encode($myFields)."', true);\n?>\n")) {


            }
			
			$old = array_keys($genericObject[$objectName]);
			$new = array_keys($myFields);
			
			// if changes regarding the amount/sort-order of the field-names were detected
			if ($old !== $new)
			{
				// TIMESTAMP-ACTION-MODELNAME-USERID
				$bp1 = $backuppath . time() . '-' . $objectName . '-' . $_SESSION[$projectName]['special']['user']['id'];
				
				
				// we need to lookup for all Fields with Type "Model"
				require $projectPath . '__model.php';
				require $projectPath . '__database.php';
				
				// 
				$dbn = $projectName.'\\DB';
				
				// we have to look for MODELs
				// 1. loop all objects
				$updated = 0;
				
				foreach ($objects as $objectname => $object)
				{
					// 2. loop all fields inside the object 
					foreach ($object['col'] as $fieldname => $field)
					{
						// if the field is a model
						// the content/structure is {"name":"MODELNAME","fields":{"xxx":{"value":"abc"},"yyy":{"value":"def"}} }
						if ($field['type'] == 'MODEL')
						{
							$bp2 = $bp1 . '-' . $fieldname . '-';
							try
							{
								// prepare the lookup query ( search for '%"__TEMPLATE__":{"value":"MODELNAME"% )
								$prepare = $prepare = $dbn::instance(intval($object['db']))->prepare('SELECT `id`, `'.$fieldname.'` as j FROM `'.$objectname.'` WHERE `'.$fieldname.'` LIKE ?');
								// prepare the update query
								$prepare2 = $dbn::instance(intval($object['db']))->prepare('UPDATE `'.$objectname.'` SET `'.$fieldname.'` = ? WHERE `id` = ?;');
								
								// loop the entries
								$prepare->execute(array('%"__TEMPLATE__":{"value":"'.$objectName.'"%'));

								while ($row = $prepare->fetch())
								{
									// backup?
									file_put_contents( $bp2 . $row->id . '.php', '<?php $b=\''.$row->j.'\';' );
									
									// (try to) adapt the field-json
									$adaptedFields = array();
									if ($current = json_decode($row->j, true))
									{
										foreach ($new as $k)
										{
											// get the current value or create a new array
											$adaptedFields[$k] = ( isset($current[$k]) ? $current[$k] : array('value'=>'') );
										}
										$current = $adaptedFields;
										//print_r($current);

										// write the json back to the db
										$prepare2->execute( array(json_encode($current), $row->id) );
										$updated++;
									}
								}
							}
							catch (exception $e)
							{
								echo $e;
							}
						}
					}
				}
				echo $updated . ' ' . L('database_entries_uptdated');
			}
			else
			{
				echo L('no_database_entries_updated');
			}
		}
		else
		{
			echo L('json_is_corrupt');
		}
	break;
	



}//switch($action) END
	
