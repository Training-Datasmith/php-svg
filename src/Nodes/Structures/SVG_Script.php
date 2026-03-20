<?php

declare (strict_types=1);
namespace SVG\Nodes\Structures;

use SVG\Nodes\C_Data_Container;
use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'script'.
 */
class Svg_Script extends Svg_Node_Container implements C_Data_Container
{
    public const TAG_NAME = 'script';
    /**
     * @param string $content The script content.
     */
    public function __construct(string $content = '')
    {
        parent::__construct();
        $this->set_value($content);
    }
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
    }
}