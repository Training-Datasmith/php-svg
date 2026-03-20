<?php

declare (strict_types=1);
namespace SVG\Nodes\Shapes;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\Rasterization\Transform\Transform_Parser;
use SVG\Utilities\Units\Length;
/**
 * Represents the SVG tag 'rect'.
 * Has the special attributes x, y, width, height, rx, ry.
 */
class Svg_Rect extends Svg_Node_Container
{
    public const TAG_NAME = 'rect';
    /**
     * @param mixed $x      The x coordinate of the upper left corner.
     * @param mixed $y      The y coordinate of the upper left corner.
     * @param mixed $width  The width.
     * @param mixed $height The height.
     */
    public function __construct($x = null, $y = null, $width = null, $height = null)
    {
        parent::__construct();
        $this->set_attribute('x', $x);
        $this->set_attribute('y', $y);
        $this->set_attribute('width', $width);
        $this->set_attribute('height', $height);
    }
    /**
     * @return string|null The x coordinate of the upper left corner.
     */
    public function get_x(): ?string
    {
        return $this->get_attribute('x');
    }
    /**
     * Sets the x coordinate of the upper left corner.
     *
     * @param mixed $x The new coordinate.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_x($x): Svg_Rect
    {
        return $this->set_attribute('x', $x);
    }
    /**
     * @return string|null The y coordinate of the upper left corner.
     */
    public function get_y(): ?string
    {
        return $this->get_attribute('y');
    }
    /**
     * Sets the y coordinate of the upper left corner.
     *
     * @param mixed $y The new coordinate.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_y($y): Svg_Rect
    {
        return $this->set_attribute('y', $y);
    }
    /**
     * @return string|null The width.
     */
    public function get_width(): ?string
    {
        return $this->get_attribute('width');
    }
    /**
     * @param mixed $width The new width.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_width($width): Svg_Rect
    {
        return $this->set_attribute('width', $width);
    }
    /**
     * @return string|null The height.
     */
    public function get_height(): ?string
    {
        return $this->get_attribute('height');
    }
    /**
     * @param mixed $height The new height.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_height($height): Svg_Rect
    {
        return $this->set_attribute('height', $height);
    }
    /**
     * @return string|null The x radius of the corners.
     */
    public function get_rx(): ?string
    {
        return $this->get_attribute('rx');
    }
    /**
     * Sets the x radius of the corners.
     *
     * @param mixed $rx The new radius.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_rx($rx): Svg_Rect
    {
        return $this->set_attribute('rx', $rx);
    }
    /**
     * @return string|null The y radius of the corners.
     */
    public function get_ry(): ?string
    {
        return $this->get_attribute('ry');
    }
    /**
     * Sets the y radius of the corners.
     *
     * @param mixed $ry The new radius.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_ry($ry): Svg_Rect
    {
        return $this->set_attribute('ry', $ry);
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
        $rasterizer->render('rect', ['x' => Length::convert($this->get_x(), $rasterizer->get_document_width()), 'y' => Length::convert($this->get_y(), $rasterizer->get_document_height()), 'width' => Length::convert($this->get_width(), $rasterizer->get_document_width()), 'height' => Length::convert($this->get_height(), $rasterizer->get_document_height()), 'rx' => Length::convert($this->get_rx(), $rasterizer->get_document_width()), 'ry' => Length::convert($this->get_ry(), $rasterizer->get_document_height())], $this);
        $rasterizer->pop_transform();
    }
}