<?php

declare (strict_types=1);
namespace SVG\Rasterization\Renderers;

use SVG\Fonts\Font_Registry;
use SVG\Rasterization\Transform\Transform;
/**
 * This renderer can draw rectangles.
 *
 * Options:
 * - float x: the x coordinate of the upper left corner
 * - float y: the y coordinate of the upper left corner
 * - float width: the width
 * - float height: the height
 * - float rx: the x radius of the corners.
 * - float ry: the y radius of the corners.
 */
class Rect_Renderer extends Multi_Pass_Renderer
{
    /**
     * @inheritdoc
     */
    protected function prepare_render_params(array $options, Transform $transform, ?Font_Registry $font_registry): ?array
    {
        $w = $options['width'];
        $h = $options['height'];
        $transform->resize($w, $h);
        if ($w <= 0 || $h <= 0) {
            return null;
        }
        $x1 = $options['x'] ?? 0;
        $y1 = $options['y'] ?? 0;
        $transform->map($x1, $y1);
        // Corner radii may at most be (width-1)/2 pixels long.
        // Anything larger than that and the circles start expanding beyond the rectangle.
        $rx = empty($options['rx']) ? 0 : $options['rx'];
        $ry = empty($options['ry']) ? 0 : $options['ry'];
        $transform->resize($rx, $ry);
        if ($rx > ($w - 1) / 2) {
            $rx = floor(($w - 1) / 2);
        }
        if ($rx < 0) {
            $rx = 0;
        }
        if ($ry > ($h - 1) / 2) {
            $ry = floor(($h - 1) / 2);
        }
        if ($ry < 0) {
            $ry = 0;
        }
        return ['x1' => $x1, 'y1' => $y1, 'x2' => $x1 + $w - 1, 'y2' => $y1 + $h - 1, 'rx' => $rx, 'ry' => $ry];
    }
    /**
     * @inheritdoc
     */
    protected function render_fill($image, $params, int $color): void
    {
        if ($params['rx'] != 0 || $params['ry'] != 0) {
            $this->render_fill_rounded($image, $params, $color);
            return;
        }
        imagefilledrectangle($image, $params['x1'], $params['y1'], $params['x2'], $params['y2'], $color);
    }
    private function render_fill_rounded($image, array $params, int $color): void
    {
        $x1 = $params['x1'];
        $y1 = $params['y1'];
        $x2 = $params['x2'];
        $y2 = $params['y2'];
        $rx = $params['rx'];
        $ry = $params['ry'];
        // draws 3 non-overlapping rectangles so that transparency is preserved
        // full vertical area
        imagefilledrectangle($image, $x1 + $rx, $y1, $x2 - $rx, $y2, $color);
        // left side
        imagefilledrectangle($image, $x1, $y1 + $ry, $x1 + $rx - 1, $y2 - $ry, $color);
        // right side
        imagefilledrectangle($image, $x2 - $rx + 1, $y1 + $ry, $x2, $y2 - $ry, $color);
        // prepares a separate image containing the corners ellipse, which is
        // then copied onto $image at the corner positions
        $corners = imagecreatetruecolor($rx * 2 + 1, $ry * 2 + 1);
        imagealphablending($corners, true);
        imagesavealpha($corners, true);
        imagefill($corners, 0, 0, 0x7f000000);
        imagefilledellipse($corners, $rx, $ry, $rx * 2, $ry * 2, $color);
        // left-top
        imagecopy($image, $corners, $x1, $y1, 0, 0, $rx, $ry);
        // right-top
        imagecopy($image, $corners, $x2 - $rx + 1, $y1, $rx + 1, 0, $rx, $ry);
        // left-bottom
        imagecopy($image, $corners, $x1, $y2 - $ry + 1, 0, $ry + 1, $rx, $ry);
        // right-bottom
        imagecopy($image, $corners, $x2 - $rx + 1, $y2 - $ry + 1, $rx + 1, $ry + 1, $rx, $ry);
        imagedestroy($corners);
    }
    /**
     * @inheritdoc
     */
    protected function render_stroke($image, $params, int $color, float $stroke_width): void
    {
        imagesetthickness($image, round($stroke_width));
        if ($params['rx'] != 0 || $params['ry'] != 0) {
            $this->render_stroke_rounded($image, $params, $color, $stroke_width);
            return;
        }
        $x1 = $params['x1'];
        $y1 = $params['y1'];
        $x2 = $params['x2'];
        $y2 = $params['y2'];
        // imagerectangle draws left and right side 1px thicker than it should,
        // and drawing 4 lines instead doesn't work either because of
        // unpredictable positioning as well as overlaps,
        // so we draw four filled rectangles instead
        $half_stroke_floor = floor($stroke_width / 2);
        $half_stroke_ceil = ceil($stroke_width / 2);
        // top
        imagefilledrectangle($image, $x1 - $half_stroke_floor, $y1 - $half_stroke_floor, $x2 + $half_stroke_floor, $y1 + $half_stroke_ceil - 1, $color);
        // bottom
        imagefilledrectangle($image, $x1 - $half_stroke_floor, $y2 - $half_stroke_ceil + 1, $x2 + $half_stroke_floor, $y2 + $half_stroke_floor, $color);
        // left
        imagefilledrectangle($image, $x1 - $half_stroke_floor, $y1 + $half_stroke_ceil, $x1 + $half_stroke_ceil - 1, $y2 - $half_stroke_ceil, $color);
        // right
        imagefilledrectangle($image, $x2 - $half_stroke_ceil + 1, $y1 + $half_stroke_ceil, $x2 + $half_stroke_floor, $y2 - $half_stroke_ceil, $color);
    }
    private function render_stroke_rounded($image, array $params, int $color, float $stroke_width): void
    {
        $x1 = $params['x1'];
        $y1 = $params['y1'];
        $x2 = $params['x2'];
        $y2 = $params['y2'];
        $rx = $params['rx'];
        $ry = $params['ry'];
        $half_stroke_floor = floor($stroke_width / 2);
        $half_stroke_ceil = ceil($stroke_width / 2);
        // top
        imagefilledrectangle($image, $x1 + $rx + 1, $y1 - $half_stroke_floor, $x2 - $rx - 1, $y1 + $half_stroke_ceil - 1, $color);
        // bottom
        imagefilledrectangle($image, $x1 + $rx + 1, $y2 - $half_stroke_ceil + 1, $x2 - $rx - 1, $y2 + $half_stroke_floor, $color);
        // left
        imagefilledrectangle($image, $x1 - $half_stroke_floor, $y1 + $ry + 1, $x1 + $half_stroke_ceil - 1, $y2 - $ry - 1, $color);
        // right
        imagefilledrectangle($image, $x2 - $half_stroke_ceil + 1, $y1 + $ry + 1, $x2 + $half_stroke_floor, $y2 - $ry - 1, $color);
        imagesetthickness($image, 1);
        for ($sw = -$half_stroke_floor; $sw < $half_stroke_ceil; ++$sw) {
            $arc_w = $rx * 2 + 1 + $sw * 2;
            $arc_h = $ry * 2 + 1 + $sw * 2;
            // left-top
            imagearc($image, $x1 + $rx, $y1 + $ry, $arc_w, $arc_h, 180, 270, $color);
            // right-top
            imagearc($image, $x2 - $rx, $y1 + $ry, $arc_w, $arc_h, 270, 360, $color);
            // left-bottom
            imagearc($image, $x1 + $rx, $y2 - $ry, $arc_w, $arc_h, 90, 180, $color);
            // right-bottom
            imagearc($image, $x2 - $rx, $y2 - $ry, $arc_w, $arc_h, 0, 90, $color);
        }
    }
}