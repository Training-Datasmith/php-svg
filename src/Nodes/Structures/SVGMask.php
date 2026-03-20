<?php

declare (strict_types=1);
namespace SVG\Nodes\Structures;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'mask'.
 */
class Svg_Mask extends Svg_Node_Container
{
    public const TAG_NAME = 'mask';
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}