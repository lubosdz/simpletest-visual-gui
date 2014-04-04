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

<style type="text/css">
/* <![CDATA[ */
#tests .span3{
	width: 23.0769%;
}
#tests .span9{
	width: 74.359%;
}
#tests .pull-left{
	float: left;
}
#tests .pull-right{
	float: right;
}
#tests .color-red {
	color: #CC0000;
}

#tests hr {
	margin: 8px 0;
}
.margin-bottom-10 {
	margin-bottom: 10px;
}
#tests input[type="radio"], #tests input[type="checkbox"] {
	margin: 0;
}
#tests button, #tests input, #tests select, #tests textarea {
	font-size: 100%;
	margin: 0;
	vertical-align: middle;
	font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
}
#tests label {
	color: #000000;
	font-size: 13px;
	display: block;
	margin-bottom: 5px;
	font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
}
#tests .alert {
	text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
}
#tests .alert {
	background-color: #FCF8E3;
	border: 1px solid #FBEED5;
	border-radius: 4px;
	margin-bottom: 20px;
	padding: 8px 12px;
	text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
}
#tests .alert-info {
	background-color: #D9EDF7;
	border-color: #BCE8F1;
	color: #3A87AD;
}
#tests .btn-u-orange {
	background: none repeat scroll 0 0 #E67E22 !important;
}
#tests .btn-u {
	background: none repeat scroll 0 0 #72C02C;
	border: 0 none;
	color: #FFFFFF !important;
	cursor: pointer;
	display: inline-block;
	font-size: 14px;
	padding: 5px 13px;
	position: relative;
	text-decoration: none !important;
}
/* ]]> */
</style>
</head>
<body>

<div id="tests">
	<div class="span3 pull-left alert alert-info"><?php echo self::$output_list_tests; ?></div>
	<div class="span9 pull-right">
		<?php echo implode("\n", $this->errors); ?>
		<?php echo self::$output_result_tests; ?>
	</div>
</div>

</body>
</html>


