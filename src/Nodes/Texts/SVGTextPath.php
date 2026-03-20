<?php

declare (strict_types=1);
namespace SVG\Nodes\Texts;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'textPath'.
 */
class Svg_Text_Path extends Svg_Node_Container
{
    public const TAG_NAME = 'textPath';
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}