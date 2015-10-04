<?php

class MovableTypeSingleStart
{
	// The temporarly local zip file created
	public $localfile = 'mt.zip';

	// The URL where the current download link can be found
	public $downloadURL = 'https://s3-ap-northeast-1.amazonaws.com/heroku-buildpack-mt/MTOS-5.2.13.zip';

	public function __construct()
	{
		// Controller
		if (isset($_REQUEST['task']) && $_REQUEST['task'] == 'go')
		{
			$this->download();
		}
		else
		{
			$this->start();
			file_put_contents(__DIR__ . '/progress.json', null);
		}
	}

	// Model
	function download()
	{
		if ($this->getZip())
		{
			// get the absolute path to $file
			$path = pathinfo(realpath($this->localfile), PATHINFO_DIRNAME);

			$zip = new ZipArchive;
			$res = $zip->open($this->localfile);

			if ($res === true)
			{
				// extract it to the path we determined above
				$zip->extractTo($path);
				$zip->close();

				if (!file_exists('mt.cgi')) {
					$dh = opendir($path);
					while (($entry = readdir($dh)) != false) {
						if (is_dir($entry) && $entry !== '.' && $entry !== '..') {
							$dir = $entry;
						}
					}
					closedir($dh);

					system("mv $path/$dir/* $path/");
                                        rmdir("$path/$dir");
                                }

				system("chmod 705 $path/*.cgi");

				unlink($this->localfile);
				exit(true);
			}
			else
			{
				echo "No Go, Bro.";
			}
		}
		else
		{
			echo "Dude, no DL.";
		}
	}

	function getZip()
	{
		set_time_limit(0);
		$fp = fopen($this->localfile, 'w+');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->downloadURL);
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_FILE, $fp); // write curl response to file
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_NOPROGRESS, false);
		curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($total, $downloaded)
		{
			file_put_contents(__DIR__ . '/progress.json', json_encode(array('progress' => round(($downloaded / $total) * 100))));
		});
		$result = curl_exec($ch);
		$error  = curl_error($ch);
		curl_close($ch);
		fclose($fp);

		unlink(__DIR__ . '/progress.json');
		unlink(__DIR__ . '/start.php');

		return $result;
	}

	// View
	function start()
	{ ?>

		<!DOCTYPE html>
		<html lang="en">
		<head>
			<title>Movable Type Single Page Installer</title>
			<link href="//netdna.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
			<link rel="icon" href="https://raw.githubusercontent.com/movabletype/movabletype/mt5.2.13/mt-static/images/favicon.ico">
			<style type="text/css">
				a.navbar-brand img {
					max-height: 100%;
					display: inline;
					padding-right: 10px;
				}
			</style>
			<meta name="viewport" content="width=device-width, initial-scale=1.0">

		</head>
		<body>
		<div class="container">
			<!-- Static navbar -->
			<div class="navbar navbar-default">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="#">
						<img src="https://raw.githubusercontent.com/movabletype/movabletype/mt5.2.13/mt-static/images/logo/movable-type-logo.png" alt="Movable Type logo"/>
						<span>Single Page Installer</span>
					</a>
				</div>
			</div>

			<?php
			if (!is_writable(dirname($this->localfile)))
			{
				$dir_class = "alert alert-danger";
				$msg       = "<p class='alert alert-danger'><span class='glyphicon glyphicon-warning-sign'></span> This path is not writable. Please change permissions before continuing.</p>";
				$continue  = "disabled";
			}
			else
			{
				$dir_class = "well well-small";
				$msg       = "";
				$continue  = "";
			}
			?>

			<?php echo $msg; ?>

			<!-- Main component for a primary marketing message or call to action -->
			<div class="jumbotron">
				<h1>Start Installation</h1>

				<p>This installer will download Movable Type from specified URL! release and prepare the installation directly on your server. The following
					directory will be used:</p>

				<p class="<?php echo $dir_class; ?>"><?php echo __DIR__; ?></p>

				<div class="hide progressMsg">
					Progress: <span class="label label-info">downloading Movable Type!</span>
				</div>
				<div class="progress progress-striped ">
					<div class="progress-bar progress-bar-info" role="progressbar" style="width: 0%">
					</div>
				</div>
				<p><a id="go" class="btn btn-primary btn-lg <?php echo $continue; ?>" data-loading-text="Downloading...">Download & Start Install</a>
				</p>
			</div>

		</div>
		<!-- /container -->

		<script src="//code.jquery.com/jquery.js"></script>
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
		<script type="text/javascript">
			var finished = false;
			function progress() {
				jQuery.ajax({'url': 'progress.json', 'type': 'post', 'dataType': 'json'})
					.done(function (msg) {
						jQuery('.progress-bar').css('width', msg.progress + '%');
						jQuery('.label-info').html(msg.progress + '%');
					});
				if (!( finished )) {
					setTimeout(function () {
						progress();
					}, 100);
				}
			}
			jQuery(document).ready(function () {
				jQuery('#go').click(function () {
					jQuery('.progress').removeClass('hide');
					jQuery('.progressMsg').removeClass('hide');
					jQuery('#go').addClass('hide');
					setTimeout(function () {
						progress();
					}, 1000);
					jQuery.ajax({'url': 'start.php', 'data': {'task': 'go'}, 'type': 'post', 'dataType': 'text'})
						.done(function () {
							jQuery('.progress-bar').css('width', '100%');
							jQuery('.label-info').html('100%');
							finished = true;
							window.location = 'mt-wizard.cgi';
						});
				});
			})
		</script>
		</body>
		</html>
	<?php }
}

new MovableTypeSingleStart;
