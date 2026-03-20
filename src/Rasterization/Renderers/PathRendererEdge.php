<?php

declare (strict_types=1);
namespace SVG\Rasterization\Renderers;

/**
 * A mutable data structure used by the PathRenderer during scanline fill.
 */
class Path_Renderer_Edge
{
    /**
     * @var float The smaller of the two y values.
     */
    public float $min_y;
    /**
     * @var float The larger of the two y values.
     */
    public float $max_y;
    /**
     * @var int The vertical winding direction of this edge, 1 if top to bottom, -1 if bottom to top.
     */
    public int $direction;
    /**
     * @var float Delta x over delta y of this edge, or 0 if the edge is fully horizontal (dy === 0).
     */
    public float $inverse_slope;
    /**
     * @var float Initially, the x coordinate belonging to the maxY value, but slides upwards during scanning.
     */
    public float $x;
    /**
     * Construct a new edge object from the two end points. The order of points is important here,
     * for computing the edge direction.
     *
     * @param $x1 float First point X.
     * @param $y1 float First point Y.
     * @param $x2 float Second point X.
     * @param $y2 float Second point Y.
     */
    public function __construct(float $x1, float $y1, float $x2, float $y2)
    {
        $this->min_y = min($y1, $y2);
        $this->max_y = max($y1, $y2);
        $this->direction = $y1 > $y2 ? -1 : 1;
        // NOTE: do not compare ($y1 === $y2) strictly, because in PHP, (4.0 === 4) is false!
        $this->inverse_slope = $y1 == $y2 ? 0.0 : ($x1 - $x2) / ($y1 - $y2);
        $this->x = $y1 > $y2 ? $x1 : $x2;
    }
    /**
     * Comparator function for sorting edges by their $maxY descending.
     *
     * @param $a self The first edge.
     * @param $b self The second edge.
     * @return int Comparison result.
     */
    public static function compare_max_y(self $a, self $b): int
    {
        return $b->max_y <=> $a->max_y;
    }
    /**
     * Comparator function for sorting edges by their $x descending.
     *
     * @param $a self The first edge.
     * @param $b self The second edge.
     * @return int Comparison result.
     */
    public static function compare_x(self $a, self $b): int
    {
        return $b->x <=> $a->x;
    }
}