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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
	<script type="text/javascript" src="?jquery"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style type="text/css">
/* <![CDATA[ */
#tests .span2{
	width: 11.535%;
}
#tests .span3{
	width: 23.0769%;
}
#tests .span9{
	width: 74.355%;
}
#tests .span10{
	width: 80.126%;
}
#tests .span12{
	width: 99.999%;
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
#tests .results table{
	width: 100%;
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
pre {
	background-color: #F5F5F5;
	border: 1px solid rgba(0, 0, 0, 0.15);
	border-radius: 4px;
	display: block;
	font-size: 13px;
	line-height: 20px;
	margin: 0 0 10px;
	padding: 9.5px;
	white-space: pre-wrap;
	word-break: break-all;
	word-wrap: break-word;
}
code, pre {
	border-radius: 3px;
	color: #333333;
	font-family: Monaco,Menlo,Consolas,"Courier New",monospace;
	font-size: 12px;
	padding: 0 3px 2px;
}
.test-snapshot-link a{
	border-bottom: 1px dashed;
	color: #72C02C;
	text-decoration: none;
}
.test-snapshot-link a:hover {
	border-bottom: 1px solid;
	text-decoration: none;
}



/* ]]> */
</style>
</head>
<body>

<div id="tests">
	<div class="span3 pull-left alert alert-info"><?php echo self::$output_list_tests; ?></div>
	<div class="span9 results pull-right">
		<?php echo implode("\n", $this->errors); ?>
		<?php echo self::$output_result_tests; ?>
	</div>
</div>


<script type="text/javascript">
/* <![CDATA[ */
$(document).ready(function(){
	$("#toggelAllUnittests").off().on("click",function(){
		var isChecked = $(this).prop("checked") ? "checked" : false;
		$("input[id*=runtest_]").prop("checked", isChecked);
	});
	$("input.toggleChildren").off().on("click",function(){
		var isChecked = $(this).prop("checked") ? "checked" : false;
		$("input[id*="+$(this).attr("id")+"_]").prop("checked", isChecked);
	});
});
/* ]]> */
</script>


</body>
</html>


