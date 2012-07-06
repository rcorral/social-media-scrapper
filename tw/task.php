<?php
// Decide what the user whats to do
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>Configuration</title>
	<link rel="stylesheet" href="assets/css/main.css" type="text/css" />
	<script type="text/javascript" src="assets/js/jquery.js"></script>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('#a_advanced').click(function(){
				jQuery('#arrow-right').toggle();
				jQuery('#arrow-down').toggle();
				jQuery('#advanced_options').slideToggle(1000);
			})

			jQuery("input[name='action']").click(function(){
				if(jQuery(this).val() != 'searcher'){
					jQuery('#p_force_xls').slideUp();
					jQuery('#p_force_todo').slideUp();
				}else{
					jQuery('#p_force_xls').slideDown();
					jQuery('#p_force_todo').slideDown();
				}
			})

			jQuery('#sm_form').submit(function(){
				if(!jQuery("input[name='action']:checked").val()){
					alert('Please select an action before submitting the form.');
					return false;
				}
				if(jQuery('#user_id').val() == ''){
					alert('Please enter a user id before submitting the form.');
					return false;
				}

				return true;
			})
		})
	</script>
</head>
<body>
<h2>Pick an option</h2>
<form action="" method="get" id="sm_form">
	<p id="p_action">
		<strong>Action:</strong><br />
		<input type="radio" name="action" value="searcher" id="searcher" /><label for="searcher">Searcher</label><br />
		<input type="radio" name="action" value="worker" id="worker" /><label for="worker">Worker</label><br />
		<input type="radio" name="action" value="viewer" id="viewer" /><label for="viewer">Viewer</label><br />
		<input type="radio" name="action" value="download" id="download" /><label for="download">Download</label><br />
	</p>

	<p id="p_user_id">
		<label for="user_id">User id:</label>
			<input type="text" name="user_id" value="" id="user_id" />
	</p>

	<a href="javascript:void(0);" id="a_advanced">Advanced options</a>
	<div id="wrap">
		<div id="arrow-right">
			<div id="arrow-right-1"></div>
			<div id="arrow-right-2"></div>
		</div>
		<div id="arrow-down" style="display:none;">
			<div id="arrow-down-1"></div>
			<div id="arrow-down-2"></div>
		</div>
	</div>
	<div class="clear"></div>

	<div id="advanced_options" style="display:none;">
		<p id="p_type">
			<label for="type">Type of Search:</label>
				<select name="type" id="type">
					<option value="1">Simple</option>
					<option value="2" selected="selected">Medium</option>
					<option value="3">Deep</option>
				</select>
		</p>

		<p id="p_force_xls">
			Force XLS on searcher:
				<input type="radio" name="force_xls" value="1" id="yes_force_xls" />
					<label for="yes_force_xls">Yes</label>
				<input type="radio" name="force_xls" value="0" id="no_force_xls" checked="checked" />
					<label for="no_force_xls">No</label>
		</p>

		<p id="p_force_todo">
			Force todo list:
				<input type="radio" name="force_todo" value="1" id="yes_force_todo" />
					<label for="yes_force_todo">Yes</label>
				<input type="radio" name="force_todo" value="0" id="no_force_todo" checked="checked" />
					<label for="no_force_todo">No</label>
			<br />If todo is built and finished, should we force a new one to be built.
		</p>

		<p id="p_cache">
			Use cache:
				<input type="radio" name="cache" value="1" id="yes_cache" checked="checked" />
					<label for="yes_cache">Yes</label>
				<input type="radio" name="cache" value="0" id="no_cache" />
					<label for="no_cache">No</label>
		</p>

		<p id="p_debug">
			Debug:
				<input type="radio" name="debug" value="1" id="yes_debug" checked="checked" />
					<label for="yes_debug">Yes</label>
				<input type="radio" name="debug" value="0" id="no_debug" />
					<label for="no_debug">No</label>
		</p>

		<p id="p_time_to_sleep">
			<label for="time_to_sleep">Time to sleep:</label>
				<input type="text" name="time_to_sleep" value="1" id="time_to_sleep" />
		</p>

		<p id="p_max_request_tries">
			<label for="max_request_tries">Max requests tries:</label>
				<input type="text" name="max_request_tries" value="2" id="max_request_tries" />
		</p>
	</div>
	
	<div class="clear"></div>

	<p>
		<input type="submit" name="submit" value="Submit" />
	</p>
</form>
</body>
</html>
<?php
// Need die right here to stop page execution.
die();
?>