<?php

declare (strict_types=1);
namespace SVG\Nodes\Shapes;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\Rasterization\Transform\Transform_Parser;
use SVG\Utilities\Units\Length;
/**
 * Represents the SVG tag 'line'.
 * Has the special attributes x1, y1, x2, y2.
 */
class Svg_Line extends Svg_Node_Container
{
    public const TAG_NAME = 'line';
    /**
     * @param mixed $x1 The first point's x coordinate.
     * @param mixed $y1 The first point's y coordinate.
     * @param mixed $x2 The second point's x coordinate.
     * @param mixed $y2 The second point's y coordinate.
     */
    public function __construct($x1 = null, $y1 = null, $x2 = null, $y2 = null)
    {
        parent::__construct();
        $this->set_attribute('x1', $x1);
        $this->set_attribute('y1', $y1);
        $this->set_attribute('x2', $x2);
        $this->set_attribute('y2', $y2);
    }
    /**
     * @return string|null The first point's x coordinate.
     */
    public function get_x1(): ?string
    {
        return $this->get_attribute('x1');
    }
    /**
     * Sets the first point's x coordinate.
     *
     * @param mixed $x1 The new coordinate.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_x1($x1): Svg_Line
    {
        return $this->set_attribute('x1', $x1);
    }
    /**
     * @return string|null The first point's y coordinate.
     */
    public function get_y1(): ?string
    {
        return $this->get_attribute('y1');
    }
    /**
     * Sets the first point's y coordinate.
     *
     * @param mixed $y1 The new coordinate.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_y1($y1): Svg_Line
    {
        return $this->set_attribute('y1', $y1);
    }
    /**
     * @return string|null The second point's x coordinate.
     */
    public function get_x2(): ?string
    {
        return $this->get_attribute('x2');
    }
    /**
     * Sets the second point's x coordinate.
     *
     * @param mixed $x2 The new coordinate.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_x2($x2): Svg_Line
    {
        return $this->set_attribute('x2', $x2);
    }
    /**
     * @return string|null The second point's y coordinate.
     */
    public function get_y2(): ?string
    {
        return $this->get_attribute('y2');
    }
    /**
     * Sets the second point's y coordinate.
     *
     * @param mixed $y2 The new coordinate.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_y2($y2): Svg_Line
    {
        return $this->set_attribute('y2', $y2);
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
        $rasterizer->render('line', ['x1' => Length::convert($this->get_x1(), $rasterizer->get_document_width()), 'y1' => Length::convert($this->get_y1(), $rasterizer->get_document_height()), 'x2' => Length::convert($this->get_x2(), $rasterizer->get_document_width()), 'y2' => Length::convert($this->get_y2(), $rasterizer->get_document_height())], $this);
        $rasterizer->pop_transform();
    }
}