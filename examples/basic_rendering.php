<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SVG\SVG;

// --- Example 1: Load an SVG from a string and rasterize to PNG ---
$svg_string = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
  <circle cx="100" cy="100" r="80" fill="#4A90D9" stroke="#2C5F8A" stroke-width="4"/>
  <text x="100" y="108" font-size="24" text-anchor="middle" fill="white">PHP</text>
</svg>
SVG;

$svg = SVG::fromString($svg_string);

// Rasterize to a GD image at 200x200 pixels
$gd_image = $svg->toRasterImage(200, 200);

// Save as PNG
imagepng($gd_image, __DIR__ . '/output_circle.png');
imagedestroy($gd_image);
echo "Saved: output_circle.png\n\n";

// --- Example 2: Load SVG from a file ---
// $svg = SVG::fromFile('/path/to/icon.svg');
// $image = $svg->toRasterImage(64, 64);
// imagepng($image, '/path/to/icon_64x64.png');

// --- Example 3: Rasterize at custom dimensions (scale up) ---
$small_svg = SVG::fromString('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16">
  <rect width="16" height="16" fill="red"/>
</svg>');

// Render at 4x the original size
$large_image = $small_svg->toRasterImage(64, 64);
imagepng($large_image, __DIR__ . '/output_scaled.png');
imagedestroy($large_image);
echo "Saved: output_scaled.png (16x16 SVG rendered at 64x64)\n\n";

// --- Example 4: Access and modify the SVG DOM ---
$svg2 = SVG::fromString('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
  <circle id="dot" cx="50" cy="50" r="40" fill="blue"/>
</svg>');

$doc = $svg2->getDocument();
// Change the fill color via attribute
$circle = $doc->getElementByIdRecursive('dot');
if ($circle !== null) {
    $circle->setAttribute('fill', 'green');
}

$gd = $svg2->toRasterImage(100, 100);
imagepng($gd, __DIR__ . '/output_modified.png');
imagedestroy($gd);
echo "Saved: output_modified.png (circle changed from blue to green)\n";
