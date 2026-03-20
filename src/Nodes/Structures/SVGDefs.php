<?php

declare (strict_types=1);
namespace SVG\Nodes\Structures;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'defs'.
 */
class Svg_Defs extends Svg_Node_Container
{
    public const TAG_NAME = 'defs';
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}