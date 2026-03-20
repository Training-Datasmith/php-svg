<?php

declare (strict_types=1);
namespace SVG\Nodes\Embedded;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'foreignObject'.
 */
class Svg_Foreign_Object extends Svg_Node_Container
{
    public const TAG_NAME = 'foreignObject';
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}