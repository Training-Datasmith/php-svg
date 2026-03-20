<?php

declare (strict_types=1);
namespace SVG\Rasterization\Renderers;

use SVG\Fonts\Font_Registry;
use SVG\Rasterization\Path\Path_Approximator;
use SVG\Rasterization\Transform\Transform;
/**
 * This renderer can draw arbitrary paths. It expects the paths to be given as the command format as returned by
 * PathParser. That format consists of an outer array containing one entry per command, with each such entry comprised
 * of an 'id' property and an 'args' property, which is itself an array of numbers. During render, these commands will
 * be approximated into polygonal subpaths.
 *
 * Options:
 * - array[] commands: The path commands, each containing an id string and an args array.
 * - string fill-rule: Either 'evenodd' or 'nonzero'. Defaults to 'nonzero'.
 */
class Path_Renderer extends Multi_Pass_Renderer
{
    /**
     * @inheritdoc
     */
    protected function prepare_render_params(array $options, Transform $transform, ?Font_Registry $font_registry): ?array
    {
        $approximator = new Path_Approximator($transform);
        $approximator->approximate($options['commands']);
        $subpaths = [];
        foreach ($approximator->get_subpaths() as $subpath) {
            $points = [];
            foreach ($subpath as $point) {
                $points[] = $point[0];
                $points[] = $point[1];
            }
            $subpaths[] = $points;
        }
        return ['subpaths' => $subpaths, 'fill-rule' => $options['fill-rule']];
    }
    /**
     * @inheritdoc
     */
    protected function render_fill($image, $params, int $color): void
    {
        Path_Renderer_Implementation::fill_multipath($image, $params['subpaths'], $color, $params['fill-rule']);
    }
    /**
     * @inheritdoc
     */
    protected function render_stroke($image, $params, int $color, float $stroke_width): void
    {
        foreach ($params['subpaths'] as $points) {
            Path_Renderer_Implementation::stroke_open_subpath($image, $points, $color, $stroke_width);
        }
    }
}