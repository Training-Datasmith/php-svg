<?php

declare (strict_types=1);
namespace SVG\Nodes\Structures;

use SVG\Nodes\C_Data_Container;
use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
/**
 * Represents the SVG tag 'style'.
 * Has the attribute 'type' and the CSS content.
 */
class Svg_Style extends Svg_Node_Container implements C_Data_Container
{
    public const TAG_NAME = 'style';
    /**
     * @param string $css   The CSS data rules.
     * @param string $type  The style type attribute.
     */
    public function __construct(string $css = '', string $type = 'text/css')
    {
        parent::__construct();
        $this->set_value($css);
        $this->set_type($type);
    }
    /**
     * @return string|null The type attribute.
     */
    public function get_type(): ?string
    {
        return $this->get_attribute('type');
    }
    /**
     * @param $type string|null The type attribute.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_type(?string $type): Svg_Style
    {
        return $this->set_attribute('type', $type);
    }
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
    }
}