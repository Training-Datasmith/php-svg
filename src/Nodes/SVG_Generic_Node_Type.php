<?php

declare (strict_types=1);
namespace SVG\Nodes;

use SVG\Rasterization\Svg_Rasterizer;
/**
 * NOT INTENDED FOR USER ACCESS. This is the class that gets instantiated for
 * unknown nodes in input SVG.
 */
class Svg_Generic_Node_Type extends Svg_Node_Container
{
    private string $tag_name;
    public function __construct(string $tag_name)
    {
        parent::__construct();
        $this->tag_name = $tag_name;
    }
    /**
     * @inheritdoc
     */
    public function get_name(): string
    {
        return $this->tag_name;
    }
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        // do nothing
    }
}