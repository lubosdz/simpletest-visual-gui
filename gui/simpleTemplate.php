<?php
/**
* This is HTML template for rendering output from SimpleTest GUI
* Adjust as needed - e.g. remove <head><body> tags if embedding output into partial view etc..
*
* Following variables are available:
* - self::$output_list_tests ... HTML UL/LI list of available tests
* - self::$output_result_tests ... HTML formated results from last test runner
*/

/**
* @var TestGui
*/
$this;

$x = 1;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
	<!-- link rel="stylesheet" type="text/css" href="/public/styles/bootstrap.css" / -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script type="text/javascript">
/* <![CDATA[ */
$("#toggelAllUnittests").live("click",function(){
	var isChecked = $(this).attr("checked") ? "checked" : false;
	$("input[id*=runtest_]").attr("checked", isChecked);
});
$("input.toggleChildren").live("click",function(){
	var isChecked = $(this).attr("checked") ? "checked" : false;
	$("input[id*="+$(this).attr("id")+"_]").attr("checked", isChecked);
});
/* ]]> */
</script>
</head>
<body>

<div class="span3 pull-left alert alert-info"><?php echo self::$output_list_tests; ?></div>
<div class="span9 pull-right"><?php echo self::$output_result_tests; ?></div>

</body>
</html>


