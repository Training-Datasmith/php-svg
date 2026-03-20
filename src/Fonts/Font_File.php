<?php

declare (strict_types=1);
namespace SVG\Fonts;

/**
 * Abstract base class for font files.
 */
abstract class Font_File
{
    private string $path;
    public function __construct(string $path)
    {
        $this->path = $path;
    }
    /**
     * @return string The path of the font file.
     */
    public function get_path(): string
    {
        return $this->path;
    }
    abstract public function get_family(): string;
    abstract public function get_weight(): float;
    abstract public function is_italic(): bool;
    abstract public function is_monospace(): bool;
}