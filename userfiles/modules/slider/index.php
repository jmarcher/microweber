<?php
$settings = get_option('settings', $params['id']);
$module_template = get_option('data-template', $params['id']);

if (!$module_template and isset($params['template'])) {
    $module_template = $params['template'];
}

$module_template = str_replace('..', '', $module_template);
if (!$module_template OR $module_template == '') {
    $module_template = 'bxslider-skin-1';
}
$defaults = array(
    'images' => '',
    'primaryText' => lang('A Slider', 'modules/bxslider'),
    'secondaryText' => 'Your text here.',
    'seemoreText' => 'See more',
    'url' => '',
    'urlText' => '',
    'skin' => 'bxslider-skin-1'
);
$data = array();
$settings = get_option('settings', $params['id']);
$json = json_decode($settings, true);

if (isset($json) == false or count($json) == 0) {
    $json = array(0 => $defaults);
}

$mrand = 'mw-slider-' . uniqid();

foreach ($json as $slide) {
    if (!isset($slide['skin']) or $slide['skin'] == '') {
        $slide['skin'] = 'bxslider-skin-1';
    }

    if (isset($slide['images'])) {
        $slide['images'] = is_array($slide['images']) ? $slide['images'] : explode(',', $slide['images']);
    } else {
        $slide['images'] = array();
    }

    if (!isset($slide['seemoreText'])) {
        $slide['seemoreText'] = 'See more';
    }
    $module_template_clean = str_replace('.php', '', $module_template);
    $default_skins_path = $config['path_to_module'] . 'templates/' . $module_template_clean . '/skins';
    $template_skins_path = template_dir() . 'modules/slider/templates/' . $module_template_clean . '/skins';


    $skin_file = $config['path_to_module'] . 'templates/' . $module_template_clean . '/skins/' . $slide['skin'] . '.php';
    $skin_default = $config['path_to_module'] . 'templates/' . $module_template_clean . '/skins/bxslider-skin-1.php';
    $skin_file_from_template = template_dir() . 'modules/slider/templates/' . $module_template_clean . '/skins/' . $slide['skin'] . '.php';

    $skin_file_full_path = normalize_path($skin_file, false);
    $skin_file = normalize_path($skin_file, false);
    $skin_file_from_template = normalize_path($skin_file_from_template, false);

    if (is_file($skin_file_from_template)) {
        $skin_file_full_path = ($skin_file_from_template);
    } elseif (is_file($skin_file)) {
        $skin_file_full_path = ($skin_file);
    } else {
        $skin_file_full_path = ($skin_default);
    }

    if (!isset($slide['skin_file'])) {
        $slide['skin_file'] = $skin_file_full_path;
    }
    $data[] = $slide;
}


if ($module_template == false and isset($params['template'])) {
    $module_template = $params['template'];
}
if ($module_template != false) {
    $template_file = module_templates($config['module'], $module_template);
} else {
    $template_file = module_templates($config['module'], 'bxskuder-skin-1');
}
if (is_file($template_file)) {
    include($template_file);
}

include('options.php');

?>


<?php if ($engine == 'bxslider'): ?>
    <script>mw.lib.require('bxslider');</script>

    <script>
        $(document).ready(function () {
            $('.bxSlider', '#<?php print $params['id'] ?>').bxSlider({
                pager: <?php print $pager; ?>,
                controls: <?php print $controls; ?>,
                infiniteLoop: <?php print $loop; ?>,
                adaptiveHeight: <?php print $adaptiveHeight; ?>,
                speed: '<?php print $speed; ?>',

                hideControlOnEnd:  <?php print $hideControlOnEnd; ?>,
                mode: '<?php print $mode; ?>',
                prevText: '<?php print $prevText; ?>',
                nextText: '<?php print $nextText; ?>',
                prevSelector: '<?php print $prevSelector; ?>',
                nextSelector: '<?php print $nextSelector; ?>',
                captions: true,
                onSliderLoad: function () {
                    mw.trigger("mw.bxslider.onSliderLoad");
                },
                <?php if(isset($pagerCustom) AND $pagerCustom != ''): ?>
                pagerCustom: '#<?php print $params['id'] ?> .<?php print $pagerCustom; ?>'
                <?php endif; ?>
            });
        });
    </script>
<?php endif; ?>


<?php if ($engine == 'slickslider'): ?>
    <script>mw.lib.require('slick');</script>
    <script>
        $(document).ready(function () {
            var config = {
                dots: <?php print $pager; ?>,
                arrows: <?php print $controls; ?>,
                infinite: <?php print $loop; ?>,
                adaptiveHeight: <?php print $adaptiveHeight; ?>,
                autoplaySpeed: '<?php print $speed; ?>',
                //speed: '<?php print $speed; ?>',
                speed: '500',

                pauseOnHover: <?php print $pauseOnHover; ?>,
                responsive: <?php print $responsive; ?>,
                autoplay: <?php print $autoplay; ?>,
                slidesPerRow: '<?php print $slidesPerRow; ?>',
                slidesToShow: '<?php print $slidesToShow; ?>',
                slidesToScroll: '<?php print $slidesToScroll; ?>',
                centerMode: <?php print $centerMode; ?>,
                centerPadding: '0px',
                draggable: <?php print $draggable; ?>,
                fade: <?php print $fade; ?>,
                focusOnSelect: <?php print $focusOnSelect; ?>
            };
            var stime = 0;
            mw.onLive(function () {
                stime = 500;
            });
            setTimeout(function () {
                $('.slickSlider', '#<?php print $params['id'] ?>').slick(config);
            }, stime)
        });
    </script>
<?php endif; ?>
<?php print lnotif("Click here to manage slides"); ?>