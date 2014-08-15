<?php

$f3=require('lib/fatfree-master/lib/base.php');
$f3->config($f3->get("ROOT").$f3->get("BASE").'/config.ini');
$f3->config($f3->get("ROOT").$f3->get("BASE").'/secrets.ini');

#$f3->set("main_nav", array("Item 1"=>"#", "Item 2"=>"#", "Item 3"=>"#"));
#$f3->set("secondary_nav", array("Item 1"=>"#", "Item 2"=>"#", "Item 3"=>"#"));
#$f3->set("inpage_nav", array("Item 1"=>"#", "Item 2"=>"#", "Item 3"=>"#"));
$f3->set("main_nav", array());
$f3->set("secondary_nav", array());
$f3->set("inpage_nav", array());

#DO NOT MODIFY THE INTERNAL STYLE FOLDER make your own templates
#$f3->set("left_column", array($f3->get("STYLE")."/left_column.htm"));
#$f3->set("right_column", array($f3->get("STYLE")."/right_column.htm"));
$f3->set("left_column", array());
$f3->set("right_column", array());

$includes = array
(
        'functions.php',
        'http_routes.php',
        'lib/redbean/rb.php',
        'lib/FloraForm-template/FloraForm.php',
	'lib/ldapauth/lib/authenticate.php'
);

foreach ($includes as $file)
{
        require_once($f3->get("ROOT").$f3->get("BASE")."/".$file);
}

$f3->run();
