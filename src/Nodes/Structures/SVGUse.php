<?php

declare (strict_types=1);
namespace SVG\Nodes\Structures;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'use'.
 */
class Svg_Use extends Svg_Node_Container
{
    public const TAG_NAME = 'use';
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}