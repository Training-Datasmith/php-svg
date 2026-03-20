<?php

declare (strict_types=1);
namespace SVG\Nodes\Presentation;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'animateTransform'.
 */
class Svg_Animate_Transform extends Svg_Node_Container
{
    public const TAG_NAME = 'animateTransform';
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}