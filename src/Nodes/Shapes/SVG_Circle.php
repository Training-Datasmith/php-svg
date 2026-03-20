<?php

declare (strict_types=1);
namespace SVG\Nodes\Shapes;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\Rasterization\Transform\Transform_Parser;
use SVG\Utilities\Units\Length;
/**
 * Represents the SVG tag 'circle'.
 * Has the special attributes cx, cy, r.
 */
class Svg_Circle extends Svg_Node_Container
{
    public const TAG_NAME = 'circle';
    /**
     * @param mixed $cx The center's x coordinate.
     * @param mixed $cy The center's y coordinate.
     * @param mixed $r  The radius.
     */
    public function __construct($cx = null, $cy = null, $r = null)
    {
        parent::__construct();
        $this->set_attribute('cx', $cx);
        $this->set_attribute('cy', $cy);
        $this->set_attribute('r', $r);
    }
    /**
     * @return string|null The center's x coordinate.
     */
    public function get_center_x(): ?string
    {
        return $this->get_attribute('cx');
    }
    /**
     * Sets the center's x coordinate.
     *
     * @param mixed $cx The new coordinate.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_center_x($cx): Svg_Circle
    {
        return $this->set_attribute('cx', $cx);
    }
    /**
     * @return string|null The center's y coordinate.
     */
    public function get_center_y(): ?string
    {
        return $this->get_attribute('cy');
    }
    /**
     * Sets the center's y coordinate.
     *
     * @param mixed $cy The new coordinate.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_center_y($cy): Svg_Circle
    {
        return $this->set_attribute('cy', $cy);
    }
    /**
     * @return string|null The radius.
     */
    public function get_radius(): ?string
    {
        return $this->get_attribute('r');
    }
    /**
     * Sets the radius.
     *
     * @param mixed $r The new radius.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_radius($r): Svg_Circle
    {
        return $this->set_attribute('r', $r);
    }
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        if ($this->get_computed_style('display') === 'none') {
            return;
        }
        $visibility = $this->get_computed_style('visibility');
        if ($visibility === 'hidden' || $visibility === 'collapse') {
            return;
        }
        Transform_Parser::parse_transform_string($this->get_attribute('transform'), $rasterizer->push_transform());
        // https://svgwg.org/svg2-draft/geometry.html#R
        // Percentages: refer to the normalized diagonal of the current SVG viewport
        $r = Length::convert($this->get_radius(), $rasterizer->get_normalized_diagonal());
        $rasterizer->render('ellipse', ['cx' => Length::convert($this->get_center_x(), $rasterizer->get_document_width()), 'cy' => Length::convert($this->get_center_y(), $rasterizer->get_document_height()), 'rx' => $r, 'ry' => $r], $this);
        $rasterizer->pop_transform();
    }
}