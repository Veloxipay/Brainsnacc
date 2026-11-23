<?php
// Prints one teaching-script drawing as a still SVG for the video maker.
//   php video/draw.php '<spec json>' before|during|after
namespace App\Support;

require __DIR__.'/../app/Support/Drawings.php';
$spec = json_decode($argv[1] ?? 'null', true);
echo Drawings::render(is_array($spec) ? $spec : null, $argv[2] ?? 'after') ?? '';
