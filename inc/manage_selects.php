<?php
require '../../header.php';

if(empty($_GET['file'])) exit('no filename');

$gpath = $projectPath . 'generic/';

$fileName = $gpath . preg_replace('/\W/', '', $_GET['file']) . '.json';
$action = $_GET['action'];
$msg = '';

// create a new file if it not exists
if(!file_exists($fileName) && $action=='create')
{
	// Create a empty object to represent a template-selector dropdown
    $obj = array(
        '__TEMPLATE_SELECT__' => array(
            'type' => 'VARCHAR',
            'tpl' => 'string',
            'lang' => array(
                'en' => array(
                    'label' => 'Select a template'
                ),
                'de' => array(
                    'label' => 'Template wählen'
                )
            ),
            'value' => '',
            'add' => array(
                'wizard' => 'select',
                'option' => array(
                    array(
                        'label' => 'Select a template',
                        'square' => '_empty_'
                    ),
                    array(
                        'label' => 'Template name',
                        'square' => 'template_filename',
                        'round' => 'Tooltip describing the template'
                    )
                )
            )
        )
    );


    // we need the variable json with the encoded structure
    $json = json_encode($obj);


	if (file_put_contents($fileName, $json)) {
        exit('file created');
    } else{
        $msg = 'file could not be saved';
    }


}

if(file_exists($fileName) && $action=='delete')
{
	unlink($fileName);
	exit('file deleted');
}

if(file_exists($fileName) && isset($_POST['json']) && $action=='save')
{
	if(file_put_contents($fileName, json_encode(json_decode($_POST['json'])))) {
		$msg = 'file saved';
	}else{
		$msg = 'file could not be saved';
	}
	
}

if(file_exists($fileName))
{
	if(!$json = file_get_contents($fileName)) exit('json is corrupt');
}


?>
<!DOCTYPE HTML>
<html lang="en">
<head>
  <title>JSONEditor</title>
  <meta charset="utf-8" />

  <!-- json editor -->
  <link rel="stylesheet" type="text/css" href="../../../wizards/jsoneditor/jsoneditor/jsoneditor.css" />
  <script type="text/javascript" src="../../../wizards/jsoneditor/jsoneditor/jsoneditor.js"></script>


  <style type="text/css">
    body {
      font: 10.5pt arial;
      color: #4d4d4d;
      line-height: 150%;
      width: 500px;
    }

    #jsoneditor {
      width: 100%;
      height: 500px;
    }
  </style>
</head>
<body>
<span><?php echo $msg?></span>
<button id="setJSON">save</button>
<div id="jsoneditor"></div>

<form id="jsonForm" method="post" action="manage_selects.php?project=<?php echo $_GET['project']?>&file=<?php echo $_GET['file']?>&action=save"><input type="hidden" id="jsonField" name="json" /></form>

<script type="text/javascript" >
	// create the editor
	var container = document.getElementById('jsoneditor');
	var editor = new jsoneditor.JSONEditor(container);
	
	var json = <?php echo $json?>;
	
	editor.set(json);

	// save json
	document.getElementById('setJSON').onclick = function () {
		var json = editor.get();
		document.getElementById('jsonField').value = JSON.stringify(json);
		document.getElementById('jsonForm').submit();
	};
</script>

</body>
</html>
