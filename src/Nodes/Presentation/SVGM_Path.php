<?php

declare (strict_types=1);
namespace SVG\Nodes\Presentation;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'mpath'.
 */
class Svgm_Path extends Svg_Node_Container
{
    public const TAG_NAME = 'mpath';
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}