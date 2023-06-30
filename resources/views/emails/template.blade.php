<html>
	<body>
		<div style="width: 800px; margin: 0 auto; background-color: #FFFFFF ">
			<div style="margin-bottom: 20px">
                <img src="{{ $message->embed(resource_path() . "/mail-logo.png") }}">
			</div>
			<div style="font-size:14px;margin:0;padding:0;font-family:'Open Sans','Helvetica Neue','Helvetica',Helvetica,Arial,sans-serif;line-height:1.5;height:100%!important;width:100%!important">
				<div style="border: 1px solid #f0f0f0; padding: 25px; margin:0;margin-bottom:30px;color:#294661;font-family:'Open Sans','Helvetica Neue','Helvetica',Helvetica,Arial,sans-serif;font-size:16px;font-weight:300">
					<h2 style="margin:0;margin-bottom:30px;font-family:'Open Sans','Helvetica Neue','Helvetica',Helvetica,Arial,sans-serif;font-weight:300;line-height:1.5;font-size:20px;color:#294661!important">
						@yield("title")
					</h2>
                    @yield("content")
				</div>
				<div style="font-size:12px; color: #444444; text-align: center; font-family:'Open Sans','Helvetica Neue','Helvetica',Helvetica,Arial,sans-serif;">
                    {{__("The message was sent from ninjaTask site.") }}
				</div>
			</div>
		</div>	
	</body>

</html>