<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>nzbto2newznab</title>

    <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
  </head>
  <body>
    <div class="container" style="padding-top: 100px">
{if isset($key)}
<div class="row">
  <div class="col-sm-8 col-lg-6 col-sm-offset-2 col-lg-offset-3">
    <div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">Generated Apikey</h3>
  </div>
  <div class="panel-body">
		<div class="alert alert-info">
			<p>
				Please make sure you have enabled the "append password to filename" option in your nzb.to Account!!
			</p>
		</div>
    <p>This is your generated Apikey for use with SickBeard, SickRage or CouchPotato:<br /> <code>{$key}</code></p>
    <p>you are now able to add a new Newznab Provider to your favorite downloader, please use the <b>key above</b> as your apikey and set the url to <b>{$smarty.const.API_BASE}</b></p>
  </div>
</div>

    </div>
  </div>
</div>
{else}
<div class="row">
  <div class="col-sm-8 col-lg-6 col-sm-offset-2 col-lg-offset-3">
    <h2 class="page-title">Generate your Apikey</h2>
<form method="POST">
  <div class="form-group">
    <label for="exampleInputEmail1">Username</label>
    <input type="text" class="form-control" name="username" id="username" placeholder="Username">
  </div>
  <div class="form-group">
    <label for="exampleInputPassword1">Password</label>
    <input type="password" class="form-control" id="password" name="password" placeholder="Password">
  </div>
  <button type="submit" class="btn btn-default">Submit</button>
</form>
</div>
</div>
{/if}
</div>
</body>
</html>
