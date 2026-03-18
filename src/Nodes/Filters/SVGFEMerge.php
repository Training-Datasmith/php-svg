<?php

declare(strict_types=1);

namespace SVG\Nodes\Filters;

use SVG\Nodes\SVGNodeContainer;
use SVG\Rasterization\SVGRasterizer;

/**
 * Represents the SVG tag 'feMerge'.
 */
class SVGFEMerge extends SVGNodeContainer
{
    public const TAG_NAME = 'feMerge';

    /**
     * @inheritdoc
     */
    public function rasterize(SVGRasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}
