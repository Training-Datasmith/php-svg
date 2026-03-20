<?php

declare (strict_types=1);
namespace SVG\Rasterization\Path;

/**
 * This is a helper class for simple polygon construction through sequentially
 * adding absolute and relative points.
 * Relative points are resolved against a starting position and/or the previous
 * point(s), resulting in an array of only absolute coordinates when built.
 */
class Polygon_Builder
{
    /**
     * @var array[] $points The polygon being built (array of float 2-tuples).
     */
    private array $points = [];
    /**
     * @var float $posX The current x position.
     */
    private float $pos_x;
    /**
     * @var float $posY The current y position.
     */
    private float $pos_y;
    /**
     * @param float $posX The starting x position.
     * @param float $posY The starting y position.
     */
    public function __construct(float $pos_x = 0.0, float $pos_y = 0.0)
    {
        $this->pos_x = $pos_x;
        $this->pos_y = $pos_y;
    }
    /**
     * Method for obtaining the built polygon array.
     *
     * @return array[] An array of absolute points (which are float 2-tuples).
     */
    public function build(): array
    {
        return $this->points;
    }
    /**
     * Finds the very first point in this polygon, or null if none exist.
     *
     * @return float[]|null The first point, or null.
     */
    public function get_first_point(): ?array
    {
        if (empty($this->points)) {
            return null;
        }
        return $this->points[0];
    }
    /**
     * Finds the very last point in this polygon, or null if none exist.
     *
     * @return float[]|null The last point, or null.
     */
    public function get_last_point(): ?array
    {
        if (empty($this->points)) {
            return null;
        }
        return $this->points[count($this->points) - 1];
    }
    /**
     * The position is either determined by the constructor in case no point was
     * added yet, and otherwise by the last point's absolute coordinates.
     *
     * This method is similar to `getLastPoint()`, with the difference that
     * the starting position is returned instead of null.
     *
     * @return float[] The current position (either last point, or initial pos).
     */
    public function get_position(): array
    {
        return [$this->pos_x, $this->pos_y];
    }
    /**
     * Appends a point with ABSOLUTE coordinates to the end of this polygon.
     *
     * Provide null for a coordinate to use the current position for that
     * coordinate.
     *
     * @param float|null $x The point's absolute x coordinate.
     * @param float|null $y The point's absolute y coordinate.
     */
    public function add_point(?float $x, ?float $y): void
    {
        $x ??= $this->pos_x;
        $y ??= $this->pos_y;
        $this->points[] = [$x, $y];
        $this->pos_x = $x;
        $this->pos_y = $y;
    }
    /**
     * Appends a point with RELATIVE coordinates to the end of this polygon.
     *
     * The coordinates are resolved against the current position.
     * Providing null for a coordinate is the same as providing a value of 0.
     *
     * @see PolygonBuilder::getPosition() For more info on relative points.
     *
     * @param float|null $x The point's relative x coordinate.
     * @param float|null $y The point's relative y coordinate.
     */
    public function add_point_relative(?float $x, ?float $y): void
    {
        $this->pos_x += $x ?: 0;
        $this->pos_y += $y ?: 0;
        $this->points[] = [$this->pos_x, $this->pos_y];
    }
    /**
     * Appends multiple points with ABSOLUTE coordinates to this polygon.
     *
     * @param array[] $points A point array (array of float 2-tuples).
     */
    public function add_points(array $points): void
    {
        $this->points = [...$this->points, ...$points];
        $end_point = $this->points[count($this->points) - 1];
        $this->pos_x = $end_point[0];
        $this->pos_y = $end_point[1];
    }
}