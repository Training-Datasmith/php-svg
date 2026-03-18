<?php

declare(strict_types=1);

namespace SVG\Nodes\Embedded;

use SVG\Nodes\SVGNodeContainer;
use SVG\Rasterization\SVGRasterizer;

/**
 * Represents the SVG tag 'foreignObject'.
 */
class SVGForeignObject extends SVGNodeContainer
{
    public const TAG_NAME = 'foreignObject';

    /**
     * @inheritdoc
     */
    public function rasterize(SVGRasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}
