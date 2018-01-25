<?php

namespace Frontend;

use Kohana;

/* @var $title string */
/* @var $css array */
/* @var $js array */
/* @var $header string */
/* @var $messages string */
/* @var $content string */
/* @var $menu_navigation string */
?>
<!DOCTYPE html>
<html>
<head>
<title><?= $title; ?></title>
<link rel="stylesheet" type="text/css" href="/bower_components/bootstrap/dist/css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="/bower_components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css" />
<link rel="stylesheet" type="text/css" href="/assets/css/main.css" />
<?php foreach($css as $href): ?>
<link rel="stylesheet" type="text/css" href="<?= $href ?>" />
<?php endforeach; ?>

<script src="/bower_components/jquery/dist/jquery.min.js"></script>
<script src="/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="/bower_components/tinymce/tinymce.min.js"></script>
<script src="/bower_components/moment/min/moment-with-locales.min.js"></script>
<script src="/bower_components/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>
<script src="/bower_components/bootstrap3-typeahead/bootstrap3-typeahead.min.js"></script>
<script src="/assets/js/jquery.tagger.js"></script>
<script src="/assets/js/jquery.inplace.js"></script>
<script src="/assets/js/main.js"></script>
<?php foreach($js as $src): ?>
<script src="<?= $src ?>"></script>
<?php endforeach;?>
</head>
<body>

<?= $header; ?>

<div class="content row-fluid" id="main-wrap">
<?= $menu_navigation; ?>
<?php $main_col_size = trim($menu_navigation)!= ''? 10 : 12; ?>
<div class="col-sm-<?= $main_col_size; ?>">
<?= $messages; ?>
<?= $content; ?>
</div>
<div class="clearfix"></div>
</div>

<footer>
<div class="container">
<ul class="footer-info">
<li><?= Kohana::$config->load('settings')->get('credentials'); ?></li>
</ul>
</div>
</footer>
</body>
</html>
