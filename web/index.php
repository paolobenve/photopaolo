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
	<title><?php if ($options['page_title']) echo $options['page_title']; ?></title>
<!--
	<link href="css/styles.min.css" rel="stylesheet" type="text/css" /> 
-->
	<link href="css/000-controls.css" rel="stylesheet" type="text/css" />
	<link href="css/001-fonts.css" rel="stylesheet" type="text/css" />
	<link href="css/002-mobile.css" rel="stylesheet" type="text/css" />
	<link href="css/003-social.css" rel="stylesheet" type="text/css" />
<!--
	<script type="text/javascript" src="js/scripts.min.js"></script> 
-->
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
		// put the <link rel=".."> tag in <head> for getting the image thumbnail when sharing
		if ($_GET['t']) {
			if ($_GET['t'] == 'a') {
				$i = 0;
				$srcImagePaths = array();
				while (array_key_exists('s' . $i, $_GET)) {
					$srcImagePaths[] = $_GET['s' . $i];
					$i ++;
				}
				
				/*
				 * INIT BASE IMAGE FILLED WITH BACKGROUND COLOR
				 */
				 
				$tileWidth = $tileHeight = 28;
				$numberOfTiles = 3;
				$pxBetweenTiles = 1;
				$leftOffSet = $topOffSet = 1;
				 
				$mapWidth = $mapHeight = ($tileWidth + $pxBetweenTiles) * $numberOfTiles;
				 
				$mapImage = imagecreatetruecolor($mapWidth, $mapHeight);
				$bgColor = imagecolorallocate($mapImage, 50, 40, 0);
				imagefill($mapImage, 0, 0, $bgColor);
				 
				/*
				 *  PUT SRC IMAGES ON BASE IMAGE
				 */
				 
				function indexToCoords($index)
				{
				 global $tileWidth, $pxBetweenTiles, $leftOffSet, $topOffSet, $numberOfTiles;
				 
				 $x = ($index % 12) * ($tileWidth + $pxBetweenTiles) + $leftOffSet;
				 $y = floor($index / 12) * ($tileWidth + $pxBetweenTiles) + $topOffSet;
				 return Array($x, $y);
				}
				 
				foreach ($srcImagePaths as $index => $srcImagePath)
				{
				 list ($x, $y) = indexToCoords($index);
				 $tileImg = imagecreatefrompng($srcImagePath);
				 
				 imagecopy($mapImage, $tileImg, $x, $y, 0, 0, $tileWidth, $tileHeight);
				 imagedestroy($tileImg);
				}
				 
				/*
				 * RESCALE TO THUMB FORMAT
				 */
				$thumbSize = 200;
				$thumbImage = imagecreatetruecolor($thumbSize, $thumbSize);
				imagecopyresampled($thumbImage, $mapImage, 0, 0, 0, 0, $thumbSize, $thumbSize, $mapWidth, $mapWidth);
				 
				header ("Content-type: image/png");
				$imagePath = options['server_cache_path'] . "/albumcache";
				mkdir($imagePath);
				$imagePath += "/" . rand();
				// save the image in cache path
				imagejpeg($thumbImage, $imagePath);
				$media = $imagePath;
			} else {
				$media = $_GET['s0'];
			}
			//echo "i=$media\n";
			$pathInfo = pathinfo($_SERVER['PHP_SELF'])['dirname'];
			//echo "p=$pathInfo\n";
			$mediaWithPath = '/' .$media;
			if ($pathInfo != '/')
				$mediaWithPath = $pathInfo .$mediaWithPath;
			//echo "iwp=$mediaWithPath\n";
			$linkTag = '<link ';
			if ($_GET['t'] == 'i' || $_GET['t'] == 'a')
				$linkTag .= 'rel="image_src" ';
			else if ($_GET['t'] == 'v')
				$linkTag .= 'rel="video_src" ';
			$linkTag .= 'href="' . $mediaWithPath . '"';
			$linkTag .= '>';
			echo "$linkTag\n";
		}
	?>
</head>
<body>
	<div id="social">
		<div class="ssk-group ssk-rounded ssk-sticky ssk-left ssk-center ssk-count">
			<a href="" class="ssk ssk-facebook" data-ssk-ready="true"></a>
			<a href="" class="ssk ssk-twitter" data-ssk-ready="true"></a>
			<a href="" class="ssk ssk-google-plus" data-ssk-ready="true"></a>
			<a href="" class="ssk ssk-pinterest" data-ssk-ready="true"></a>
			<a href="" class="ssk ssk-tumblr" data-ssk-ready="true"></a>
			<a href="" class="ssk ssk-email" data-ssk-ready="true"></a>
		</div>
	</div>
	<div id="title-container">
		<div id="buttons-container">
			<a id="day-folders-view-link" href="javascript:void(0)">
				<div id="day-folders-view-container">
					<span id="day-view"></span>
					<span id="folders-view"></span>
				</div>
			</a>
		</div>
		<div id="title">
			<span id="title-string"></span>
			<a id="album-sort-arrows"></a>
			<a id="media-sort-arrows"></a>
		</div>
	</div>
	<div id="media-view">
		<div id="media-box">
			<div id="photo-box">
				<a class="next-media">
					<div id="photo-box-inner" ></div>
					<div id="photo-bar">
						<div class="links">
							<a class="metadata-show" href="javascript:void(0)"></a>
							<a class="metadata-hide" style="display:none;" href="javascript:void(0)"></a> |
							<a class="original-link" target="_blank"></a>
							<span class="fullscreen-divider"> | </span>
							<a class="fullscreen" href="javascript:void(0)">
								<span class="enter-fullscreen"></span>
								<span class="exit-fullscreen"></span>
							</a>
						</div>
						<div class="metadata"></div>
					</div>
				</a>
			</div>
			<div id="video-box">
				<a class="next-media">
					<div id="video-box-inner"></div>
					<div id="video-bar">
						<div class="links">
							<a class="metadata-show" href="javascript:void(0)"></a>
							<a class="metadata-hide" style="display:none;" href="javascript:void(0)"></a> |
							<a class="original-link" target="_blank"></a>
							<span class="fullscreen-divider"> | </span>
							<a class="fullscreen" href="javascript:void(0)">
								<span class="enter-fullscreen"></span>
								<span class="exit-fullscreen"></span>
							</a>
						</div>
						<div class="metadata"></div>
					</div>
				</a>
			</div>
		</div>
		
		<a id="back">&lsaquo;</a>
		<a id="next">&rsaquo;</a>
	</div>
	<div id="album-view">
		<div id="subalbums"></div>
		<div id="thumbs">
			<div id="loading"></div>
		</div>
		<div id="powered-by">
			<span id="powered-by-string"></span>
			<a href="https://github.com/paolobenve/photofloat" target="_blank">PhotoFloat</a>
		</div>
	</div>

	<div id="error-overlay"></div>
	<div id="error-options-file"></div>
	<div id="error-text-folder"></div>
	<div id="error-text-image"></div>
	<div id="auth-text"><form id="auth-form"><input id="password" type="password" /><input type="submit" value="Login" /></form</div>
</body>
</html>
