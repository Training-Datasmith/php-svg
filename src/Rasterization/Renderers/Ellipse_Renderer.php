<?php

declare (strict_types=1);
namespace SVG\Rasterization\Renderers;

use SVG\Fonts\Font_Registry;
use SVG\Rasterization\Transform\Transform;
/**
 * This renderer can draw ellipses (and circles).
 *
 * Options:
 * - float cx: x coordinate of center point
 * - float cy: y coordinate of center point
 * - float rx: radius along x-axis
 * - float ry: radius along y-axis
 */
class Ellipse_Renderer extends Multi_Pass_Renderer
{
    /**
     * @inheritdoc
     */
    protected function prepare_render_params(array $options, Transform $transform, ?Font_Registry $font_registry): ?array
    {
        $cx = $options['cx'] ?? 0;
        $cy = $options['cy'] ?? 0;
        $transform->map($cx, $cy);
        $width = ($options['rx'] ?? $options['ry'] ?? 0) * 2;
        $height = ($options['ry'] ?? $options['rx'] ?? 0) * 2;
        $transform->resize($width, $height);
        return ['cx' => $cx, 'cy' => $cy, 'width' => $width, 'height' => $height];
    }
    /**
     * @inheritdoc
     */
    protected function render_fill($image, $params, int $color): void
    {
        imagefilledellipse($image, (int) round($params['cx']), (int) round($params['cy']), (int) round($params['width']), (int) round($params['height']), $color);
    }
    /**
     * @inheritdoc
     */
    protected function render_stroke($image, $params, int $color, float $stroke_width): void
    {
        imagesetthickness($image, round($stroke_width));
        $width = (int) round($params['width']) | 1;
        $height = (int) round($params['height']) | 1;
        // imageellipse ignores imagesetthickness; draw arc instead
        imagearc($image, (int) round($params['cx']), (int) round($params['cy']), $width, $height, 0, 360, $color);
    }
}