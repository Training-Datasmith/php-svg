<?php

declare (strict_types=1);
namespace SVG\Nodes\Structures;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'clipPath'.
 */
class Svg_Clip_Path extends Svg_Node_Container
{
    public const TAG_NAME = 'clipPath';
    public function __construct(?string $id = null)
    {
        parent::__construct();
        $this->set_attribute('id', $id);
    }
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // TODO How do we rasterize this? The clipPath in and of itself wont get rasterized, but usages of it will be!
    }
}