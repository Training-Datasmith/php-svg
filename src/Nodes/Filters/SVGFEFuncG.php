<?php

declare (strict_types=1);
namespace SVG\Nodes\Filters;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'feFuncG'.
 */
class Svgfe_Func_G extends Svg_Node_Container
{
    public const TAG_NAME = 'feFuncG';
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}