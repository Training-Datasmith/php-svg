<?php

declare (strict_types=1);
namespace SVG\Rasterization\Renderers;

use SVG\Fonts\Font_Registry;
use SVG\Rasterization\Transform\Transform;
/**
 * This renderer can draw polygons and polylines.
 * The points are provided as arrays with 2 entries: 0 => x coord, 1 => y coord.
 *
 * Options:
 * - bool open: if true, leaves first and last point disconnected (-> polyline)
 * - array[] points: array of coordinate tuples (i.e., array of array of float)
 * - string fill-rule: Either 'evenodd' or 'nonzero'. Defaults to 'nonzero'.
 */
class Polygon_Renderer extends Multi_Pass_Renderer
{
    /**
     * @inheritdoc
     */
    protected function prepare_render_params(array $options, Transform $transform, ?Font_Registry $font_registry): ?array
    {
        $points = [];
        foreach ($options['points'] as $point) {
            $transform->map_into($point[0], $point[1], $points);
        }
        return ['open' => $options['open'] ?? false, 'points' => $points, 'fill-rule' => $options['fill-rule']];
    }
    /**
     * @inheritdoc
     */
    protected function render_fill($image, $params, int $color): void
    {
        // Filling a polygon is equivalent to filling a path containing just a single polygonal subpath.
        Path_Renderer_Implementation::fill_multipath($image, [$params['points']], $color, $params['fill-rule']);
    }
    /**
     * @inheritdoc
     */
    protected function render_stroke($image, $params, int $color, float $stroke_width): void
    {
        if ($params['open']) {
            Path_Renderer_Implementation::stroke_open_subpath($image, $params['points'], $color, $stroke_width);
            return;
        }
        Path_Renderer_Implementation::stroke_closed_subpath($image, $params['points'], $color, $stroke_width);
    }
}