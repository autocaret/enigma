<!DOCTYPE html>
<html>
	<head>
		<title>API</title>
	</head>
	<body>
		<script type="text/javascript">
		window.addEventListener( 'message', function( msg )
		{
			let mess = msg.data;
			if( !mess.query )
			{
				console.log( 'Could not process non-existent query.' );
				return;
			}
			
			let j = new XMLHttpRequest();
			if( mess.data )
				if( !mess.data.userId && mess.userId ) mess.data.userId = mess.userId;
			j.open( 'POST', 'transport.php', true, true );
			j.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
			j.onload = function()
			{
				let res = this.responseText.split( '<!--separate-->' );
				if( res[0] == 'ok' )
				{
					return msg.source.postMessage( { response: true, data: encodeURIComponent( res[1] ), callback: mess.callback }, '*' );
				}
				msg.source.postMessage( { response: false, data: null, callback: mess.callback }, '*' );
			}
			
			let vars = 'query=' + mess.query + '&data=' + encodeURIComponent( JSON.stringify( mess.data ) ) + '&secret=' +( mess.secret ? mess.secret : '' );
			
			j.send( vars );
		} );
		</script>
	</body>
</html>
