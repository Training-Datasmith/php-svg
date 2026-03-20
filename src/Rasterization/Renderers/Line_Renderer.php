<?php

declare (strict_types=1);
namespace SVG\Rasterization\Renderers;

use SVG\Fonts\Font_Registry;
use SVG\Rasterization\Transform\Transform;
/**
 * This renderer can draw straight lines. Filling is not supported.
 *
 * Options:
 * - float x1: first x coordinate
 * - float y1: first y coordinate
 * - float x2: second x coordinate
 * - float y2: second y coordinate
 */
class Line_Renderer extends Multi_Pass_Renderer
{
    /**
     * @inheritdoc
     */
    protected function prepare_render_params(array $options, Transform $transform, ?Font_Registry $font_registry): ?array
    {
        $x1 = $options['x1'];
        $y1 = $options['y1'];
        $transform->map($x1, $y1);
        $x2 = $options['x2'];
        $y2 = $options['y2'];
        $transform->map($x2, $y2);
        return ['x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' => $y2];
    }
    /**
     * @inheritdoc
     */
    protected function render_fill($image, $params, int $color): void
    {
        // can't fill
    }
    /**
     * @inheritdoc
     */
    protected function render_stroke($image, $params, int $color, float $stroke_width): void
    {
        imagesetthickness($image, round($stroke_width));
        imageline($image, (int) round($params['x1']), (int) round($params['y1']), (int) round($params['x2']), (int) round($params['y2']), $color);
    }
}