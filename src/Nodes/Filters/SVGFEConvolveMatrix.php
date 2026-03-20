<?php

declare (strict_types=1);
namespace SVG\Nodes\Filters;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'feConvolveMatrix'.
 */
class Svgfe_Convolve_Matrix extends Svg_Node_Container
{
    public const TAG_NAME = 'feConvolveMatrix';
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}