<?php

declare (strict_types=1);
namespace SVG\Nodes\Shapes;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\Rasterization\Transform\Transform_Parser;
use SVG\Utilities\Units\Length;
/**
 * Represents the SVG tag 'ellipse'.
 * Has the special attributes cx, cy, rx, ry.
 */
class Svg_Ellipse extends Svg_Node_Container
{
    public const TAG_NAME = 'ellipse';
    /**
     * @param mixed $cx The center's x coordinate.
     * @param mixed $cy The center's y coordinate.
     * @param mixed $rx The radius along the x-axis.
     * @param mixed $ry The radius along the y-axis.
     */
    public function __construct($cx = null, $cy = null, $rx = null, $ry = null)
    {
        parent::__construct();
        $this->set_attribute('cx', $cx);
        $this->set_attribute('cy', $cy);
        $this->set_attribute('rx', $rx);
        $this->set_attribute('ry', $ry);
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
    public function set_center_x($cx): Svg_Ellipse
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
    public function set_center_y($cy): Svg_Ellipse
    {
        return $this->set_attribute('cy', $cy);
    }
    /**
     * @return string|null The radius along the x-axis.
     */
    public function get_radius_x(): ?string
    {
        return $this->get_attribute('rx');
    }
    /**
     * Sets the radius along the x-axis.
     *
     * @param mixed $rx The new radius.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_radius_x($rx): Svg_Ellipse
    {
        return $this->set_attribute('rx', $rx);
    }
    /**
     * @return string|null The radius along the y-axis.
     */
    public function get_radius_y(): ?string
    {
        return $this->get_attribute('ry');
    }
    /**
     * Sets the radius along the y-axis.
     *
     * @param mixed $ry The new radius.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_radius_y($ry): Svg_Ellipse
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
        $rasterizer->render('ellipse', ['cx' => Length::convert($this->get_center_x(), $rasterizer->get_document_width()), 'cy' => Length::convert($this->get_center_y(), $rasterizer->get_document_height()), 'rx' => Length::convert($this->get_radius_x(), $rasterizer->get_document_width()), 'ry' => Length::convert($this->get_radius_y(), $rasterizer->get_document_height())], $this);
        $rasterizer->pop_transform();
    }
}