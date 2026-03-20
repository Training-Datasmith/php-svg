<?php

declare (strict_types=1);
namespace SVG\Nodes\Shapes;

use SVG\Rasterization\Svg_Rasterizer;
use SVG\Rasterization\Transform\Transform_Parser;
/**
 * Represents the SVG tag 'polygon'.
 * Offers methods for manipulating the list of points.
 */
class Svg_Polygon extends Svg_Polygonal_Shape
{
    public const TAG_NAME = 'polygon';
    /**
     * @param array[] $points Array of points (float 2-tuples).
     */
    public function __construct(array $points = [])
    {
        parent::__construct($points);
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
        $rasterizer->render('polygon', ['open' => false, 'points' => $this->get_points(), 'fill-rule' => strtolower($this->get_computed_style('fill-rule') ?: 'nonzero')], $this);
        $rasterizer->pop_transform();
    }
}