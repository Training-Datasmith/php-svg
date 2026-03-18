<?php

declare(strict_types=1);

namespace SVG\Nodes\Filters;

use SVG\Nodes\SVGNodeContainer;
use SVG\Rasterization\SVGRasterizer;

/**
 * Represents the SVG tag 'feTurbulence'.
 */
class SVGFETurbulence extends SVGNodeContainer
{
    public const TAG_NAME = 'feTurbulence';

    /**
     * @inheritdoc
     */
    public function rasterize(SVGRasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}
