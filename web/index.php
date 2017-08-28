<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="fragment" content="!" />
	<meta name="medium" content="image" />
	<?php
		$jsonString = file_get_contents('options.json');
		$options = json_decode($jsonString, true);
	?>
	<title><?php if ($options['page_title'])
			echo $options['page_title']; ?></title>
	<link rel="icon" href="favicon.ico" type="image/x-icon"/>

<!--
	<link href="css/styles.min.css" rel="stylesheet" type="text/css" /> 
	<script type="text/javascript" src="js/scripts.min.js"></script> 
-->

	<link href="css/000-controls.css" rel="stylesheet" type="text/css" />
	<link href="css/001-fonts.css" rel="stylesheet" type="text/css" />
	<link href="css/002-mobile.css" rel="stylesheet" type="text/css" />
	<link href="css/003-social.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="js/000-jquery-1.12.4.js"></script>
	<script type="text/javascript" src="js/001-hashchange.js"></script>
	<script type="text/javascript" src="js/002-preloadimages.js"></script>
	<script type="text/javascript" src="js/003-mousewheel.js"></script>
	<script type="text/javascript" src="js/004-fullscreen.js"></script>
	<script type="text/javascript" src="js/005-modernizr.js"></script>
	<script type="text/javascript" src="js/008-social.js"></script>
	<script type="text/javascript" src="js/009-translations.js"></script>
	<script type="text/javascript" src="js/010-libphotofloat.js"></script>
	<script type="text/javascript" src="js/012-display.js"></script>
	
	<?php
		//~ ini_set('display_errors', 1);
		//~ error_reporting(E_ALL);
		// from http://skills2earn.blogspot.it/2012/01/how-to-check-if-file-exists-on.html , solution # 3
		function url_exist($url) {
			if (@fopen($url,"r"))
				return true;
			else 
				return false;
		}
		
		// put the <link rel=".."> tag in <head> for getting the image thumbnail when sharing
		if (isset($_GET['t']) && $_GET['t']) {
			// Prevent directory traversal security vulnerability
			$realPath = realpath($_GET['m']);
			if (strpos($realPath, realpath($options['server_cache_path'])) === 0  && url_exist($realPath)) {
				$linkTag = '<link rel="';
				if ($_GET['t'] == 'p' || $_GET['t'] == 'a')
					// 'p': photo, 'a': album
					$linkTag .= 'image_src';
				else if ($_GET['t'] == 'v')
					// 'v': video
					$linkTag .= 'video_src';
				$linkTag .= '" href="' . $_GET['m'] . '"';
				$linkTag .= '>';
				echo "$linkTag\n";
			}
		}
	?>
	
	<?php if ($options['piwik_server'] && $options['piwik_id']) { ?>
		<!-- Piwik -->
		<script type="text/javascript">
			var _paq = _paq || [];
			_paq.push(['trackPageView']);
			_paq.push(['enableLinkTracking']);
			(function() {
				var u="//<?php echo $options['piwik_server']; ?>";
				_paq.push(['setTrackerUrl', u+'piwik.php']);
				_paq.push(['setSiteId', '<?php echo $options['piwik_id']; ?>']);
				var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
				g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
			})();
			// from https://piwik.org/blog/2017/02/how-to-track-single-page-websites-using-piwik-analytics/
			$(document).ready(function() {
				$(window).hashchange(function() {
					_paq.push(['setCustomUrl', '/' + window.location.hash]);
					_paq.push(['setDocumentTitle', PhotoFloat.cleanHash(location.hash)]);
					_paq.push(['trackPageView']);
				});
			});
		</script>
		<noscript><p><img src="//cathopedia.org:8080/piwik/piwik.php?idsite=15" style="border:0;" alt="" /></p></noscript>
		<!-- End Piwik Code -->
	<?php } ?>  
	
	<?php if (isset($options['google_analitics_id'])) { ?>
		<!-- google analytics -->
		<script type="text/javascript">
			// from https://git.zx2c4.com/PhotoFloat/tree/web/js/999-googletracker.js
			window._gaq = window._gaq || [];
			window._gaq.push(['_setAccount', '<?php echo $options['google_analytics_id']; ?>']);
			var ga = document.createElement('script');
			ga.type = 'text/javascript';
			ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0];
			s.parentNode.insertBefore(ga, s);
			$(document).ready(function() {
				$(window).hashchange(function() {
					window._gaq = window._gaq || [];
					window._gaq.push(['_trackPageview']);
					window._gaq.push(['_trackPageview', PhotoFloat.cleanHash(location.hash)]);
				});
			});
		</script>
		<!-- End google analitics code -->
	<?php } ?>  
</head>
<body>
	<?php
		if ($_GET)
			// redirect to parameters-less page
			echo "
	<script>
		$(document).ready(
			function() {
				window.location.href = location.origin + location.pathname + location.hash;
			});
	</script>
";
	?>
	<div id="social">
		<div class="ssk-group ssk-rounded ssk-sticky ssk-left ssk-center ssk-sm">
			<a href="" class="ssk ssk-facebook"></a>
			<a href="" class="ssk ssk-whatsapp"></a>
			<a href="" class="ssk ssk-twitter"></a>
			<a href="" class="ssk ssk-google-plus"></a>
			<a href="" class="ssk ssk-email"></a>
		</div>
	</div>
	<div id="title-container">
		<div id="buttons-container">
			<a id="day-folders-view-link" href="javascript:void(0)">
				<div id="day-folders-view-container">
					<span id="date-view"></span>
					<span id="folders-view"></span>
				</div>
			</a>
		</div>
		<div id="title">
			<span id="title-string"></span>
		</div>
	</div>
	<div id="media-view">
		<div id="media-box">
			<a id="next-media">
				<div id="media-box-inner" ></div>
			</a>
			<div id="media-bar">
				<div id="links">
					<a id="metadata-show" href="javascript:void(0)"></a>
					<a id="metadata-hide" style="display:none;" href="javascript:void(0)"></a> |
					<a id="original-link" target="_blank"></a>
					<a id="fullscreen" href="javascript:void(0)">
						<span id="fullscreen-divider"> | </span>
						<span id="enter-fullscreen"></span>
						<span id="exit-fullscreen"></span>
					</a>
				</div>
				<div id="metadata"></div>
			</div>
		</div>
		
		<a id="prev">&lsaquo;</a>
		<a id="next">&rsaquo;</a>
	</div>
	<div id="album-view">
		<div id="subalbums"></div>
		<div id="thumbs">
			<div id="loading"></div>
		</div>
		<div id="error-too-many-images"></div>
		<div id="powered-by">
			<span id="powered-by-string"></span>
			<a href="https://github.com/paolobenve/myphotoshare" target="_blank">MyPhotoShare</a>
		</div>
	</div>
	
	<div id="error-overlay"></div>
	<div id="error-options-file"></div>
	<div id="error-text-folder"></div>
	<div id="error-root-folder"></div>
	<div id="error-text-image"></div>
	<div id="auth-text"><form id="auth-form"><input id="password" type="password" /><input type="submit" value="Login" /></form></div>
</body>
</html>
