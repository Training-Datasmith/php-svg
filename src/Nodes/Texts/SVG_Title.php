<?php

declare (strict_types=1);
namespace SVG\Nodes\Texts;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'title'.
 */
class Svg_Title extends Svg_Node_Container
{
    public const TAG_NAME = 'title';
    public function __construct(string $text = '')
    {
        parent::__construct();
        $this->set_value($text);
    }
    /**
     * Dummy implementation
     *
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // nothing to rasterize
    }
}