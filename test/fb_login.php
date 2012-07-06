<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
</head>
<body>

<form id="login_form" action="https://login.facebook.com/login.php?login_attempt=1" method="POST">
	<table cellspacing="0" cellpadding="0" summary="layout table">
		<tbody>
			<tr>
				<td>
					<input type="text" value="" name="email" id="email" class="inputtext DOMControl_placeholder" />
				</td>
				<td>
					<input type="password" value="" name="pass" id="pass" class="inputpassword" />
				</td>
				<td class="login_form_last_field">
					<div class="inner">
						<span class="UIButton UIButton_Blue UIFormButton">
							<input type="submit" class="UIButton_Text" value="Login" />
						</span>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<input type="hidden" value="1" name="persistent" />
	<input type="hidden" value="en_US" name="locale" id="locale" />
	<input type="hidden" name="non_com_login" id="non_com_login" />
</form>


</body>
</html>
