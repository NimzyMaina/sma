<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<style type="text/css">
		html, body { margin: 0; padding: 0; }
		body { padding: 9px 9px 5px 9px; border: 1px solid #CCC;  }
		.preview_frame { width:100%;  }
	</style>
	<script type="text/javascript" src="<?=$assets?>js/jquery.js"></script>
	<script>
		var calcHeight = function() {
			$('.preview_frame').height($(window).height()-50);
		}

		$(document).ready(function() {
			calcHeight();
		});

		$(window).resize(function() {
			calcHeight();
		}).load(function() {
			calcHeight();
		});
	</script>
</head>
<body>
	<p style="color:#999; margin-top:0; font-size:10px;">Please edit themes/<?=$Settings->theme?>/views/promotions.php If doesn't exit then themes/default/views/promotions.php</p>
	<iframe class="preview_frame" height="400" src="http://tecdiary.com" frameborder="0" allowfullscreen></iframe>
</body>
</html>