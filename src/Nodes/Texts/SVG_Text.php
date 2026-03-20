<?php

declare (strict_types=1);
namespace SVG\Nodes\Texts;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\Rasterization\Transform\Transform_Parser;
use SVG\Utilities\Units\Length;
/**
 * Represents the SVG tag 'text'.
 *
 * Usage example:
 *
 * \SVG\SVG::addFont('./fonts/Ubuntu-Regular.ttf');
 *
 * $svg = new \SVG\SVG(600, 400);
 *
 * $svg->getDocument()->addChild(
 *   (new \SVG\Nodes\Texts\SVGText('hello', 50, 50))
 *     ->setFontFamily('Ubuntu')
 *     ->setFontSize(15)
 * );
 *
 */
class Svg_Text extends Svg_Node_Container
{
    public const TAG_NAME = 'text';
    public function __construct(string $text = '', $x = 0, $y = 0)
    {
        parent::__construct();
        $this->set_value($text);
        $this->set_attribute('x', $x);
        $this->set_attribute('y', $y);
    }
    /**
     * Set the CSS font-family property.
     *
     * @param string $fontFamily The value for the CSS font-family property.
     */
    public function set_font_family(string $font_family): Svg_Text
    {
        $this->set_style('font-family', $font_family);
        return $this;
    }
    /**
     * Set the CSS font-size property.
     *
     * @param $fontSize mixed The value for the CSS font-size property.
     */
    public function set_font_size($font_size): Svg_Text
    {
        $this->set_style('font-size', $font_size);
        return $this;
    }
    /**
     * @inheritdoc
     */
    public function get_computed_style(string $name): ?string
    {
        // force stroke before fill
        if ($name === 'paint-order') {
            // TODO remove this workaround
            return 'stroke fill';
        }
        return parent::get_computed_style($name);
    }
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        Transform_Parser::parse_transform_string($this->get_attribute('transform'), $rasterizer->push_transform());
        // TODO: support percentage font sizes
        //       https://www.w3.org/TR/SVG11/text.html#FontSizeProperty
        //       "Percentages: refer to parent element's font size"
        // For now, assume the standard font size of 16px as reference size
        // Default to 16px if font size could not be parsed
        $font_size = Length::convert($this->get_computed_style('font-size'), 16) ?? 16;
        $rasterizer->render('text', ['x' => Length::convert($this->get_attribute('x'), $rasterizer->get_document_width()), 'y' => Length::convert($this->get_attribute('y'), $rasterizer->get_document_height()), 'fontFamily' => $this->get_computed_style('font-family'), 'fontWeight' => $this->get_computed_style('font-weight'), 'fontStyle' => $this->get_computed_style('font-style'), 'fontSize' => $font_size, 'anchor' => $this->get_computed_style('text-anchor'), 'text' => $this->get_value()], $this);
        $rasterizer->pop_transform();
    }
}