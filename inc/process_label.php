<?php

/**
* 
*/
function processLabel ($arr)
{
	$out = array();
	
	foreach ($arr as $k => $str)
	{
		
		$out[$k] = array();	
		/*
							'tooltip' => false,
							'doc' => false,
							'placeholder' => false,
							'tabhead' => false,
							'accordionhead' => false,
						);*/
		
		// Documentation-File <in Tag-Brackets>
		if (preg_match('/\<([^\)]+)\>/', $str, $doc))
		{
			$str = trim(preg_replace('/\s*\<[^)]*\>/', '', $str));
			$out[$k]['doc'] = $doc[1];
		}
		
		// Tooltip (in Brackets)
		if (preg_match('/\(([^\)]+)\)/', $str, $ttip))
		{
			$str = trim(preg_replace('/\s*\([^)]*\)/', '', $str));
			$out[$k]['tooltip'] = $ttip[1];
		}
		
		// Placeholder [in square Brackets]
		if (preg_match('/\[(.*?)\]/', $str, $pa))
		{
			$str = trim(preg_replace('/\[(.*?)\]/', '', $str));
			$out[$k]['placeholder'] = $pa[1];
		}
		
		// Accordion-Limiter "--"
		$arr = explode('--', $str);
		if (count($arr) === 2)
		{
			$str = trim($arr[1]);
			$out[$k]['accordionhead'] = trim($arr[0]);
		}
		
		// Tab-Limiter "||"
		$arr = explode('||', $str);
		if (count($arr) === 2)
		{
			$str = trim($arr[1]);
			$out[$k]['tabhead'] = trim($arr[0]);
		}
		
		// define the pure Label
		$out[$k]['label'] = $str;
		
	}
	
	return $out;
}
