<?php
// --- cache location
$cache_dir = dirname(__FILE__) . '/../var/cache';
 
function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				$o = $dir . "/" . $object;
				if (filetype($o) == "dir") {
					rrmdir($dir."/".$object);
				}
				else {
					echo "<br/>" . $o;
					unlink($o);
				}
			}
		}
		reset($objects);
		rmdir($dir);
	}
}
function cc($cache_dir, $name) {
	$d = $cache_dir . '/' . $name;
	if (is_dir($d)) {
		echo "<br/><br/><b>clearing " . $name . ' :</b>';
		rrmdir($d);
	}
}
if (is_dir($cache_dir)) {
	if (basename($cache_dir) == "cache") {
		echo "<br/><br/><b>clearing cache :</b>";
		cc($cache_dir, "dev");
		// cc($cache_dir, "prod");
		// cc($cache_dir, "test");
		echo "<br/><br/><b>done !</b>";
	}
	else {
		die("<br/> Error : cache_dir not named cache ?");
	}
}
else {
	die("<br/> Error : cache_dir is not a dir");
}
?>