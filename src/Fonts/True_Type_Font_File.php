<?php

declare (strict_types=1);
namespace SVG\Fonts;

/**
 * A font file using the TTF format (TrueType).
 */
class True_Type_Font_File extends Font_File
{
    // https://learn.microsoft.com/en-us/typography/opentype/spec/otff#table-directory
    // OpenType fonts that contain TrueType outlines should use the value of 0x00010000 for the sfntVersion.
    // OpenType fonts containing CFF data (version 1 or 2) should use 0x4F54544F ('OTTO', when re-interpreted as a Tag)
    // for sfntVersion.
    private const SFNT_VERSION_TTF = 0x10000;
    private const TABLE_TAG_NAME = 'name';
    private const NAME_ID_FONT_FAMILY = 1;
    private const NAME_ID_FONT_SUBFAMILY = 2;
    private const TABLE_TAG_OS2 = 'OS/2';
    private const PLATFORM_ID_UNICODE = 0;
    private const PLATFORM_ID_MACINTOSH = 1;
    private const PLATFORM_ID_WINDOWS = 3;
    private string $family;
    private string $subfamily;
    private ?int $weight_class;
    public function __construct(string $path, string $family, string $subfamily, ?int $weight_class)
    {
        parent::__construct($path);
        $this->family = $family;
        $this->subfamily = $subfamily;
        $this->weight_class = $weight_class;
    }
    public function get_family(): string
    {
        return $this->family;
    }
    public function get_weight(): float
    {
        return $this->weight_class ?? ($this->subfamily === 'Bold' || $this->subfamily === 'Bold Italic' || $this->subfamily === 'Bold Oblique' ? 700 : 400);
    }
    public function is_italic(): bool
    {
        return $this->subfamily === 'Italic' || $this->subfamily === 'Bold Italic';
    }
    public function is_oblique(): bool
    {
        return $this->subfamily === 'Oblique' || $this->subfamily === 'Bold Oblique';
    }
    public function is_monospace(): bool
    {
        // TODO implement detection for monospace fonts
        return false;
    }
    // https://learn.microsoft.com/en-us/typography/opentype/spec/otff
    public static function read(string $path): ?True_Type_Font_File
    {
        $file = fopen($path, 'rb');
        if ($file === false) {
            return null;
        }
        $table_directory = self::read_table_directory($file);
        if ($table_directory['sfntVersion'] !== self::SFNT_VERSION_TTF) {
            fclose($file);
            return null;
        }
        // 'name' should always exist: https://learn.microsoft.com/en-us/typography/opentype/spec/otff#required-tables
        if (!self::locate_table_record($file, $table_directory, self::TABLE_TAG_NAME)) {
            fclose($file);
            return null;
        }
        $name_table_record = self::read_table_record($file);
        $name_table = self::read_naming_table($file, $name_table_record);
        $family = $name_table[self::NAME_ID_FONT_FAMILY] ?? '';
        $subfamily = $name_table[self::NAME_ID_FONT_SUBFAMILY] ?? '';
        $weight_class = null;
        if (self::locate_table_record($file, $table_directory, self::TABLE_TAG_OS2)) {
            $os2table_record = self::read_table_record($file);
            $os2Table = self::read_os2table($file, $os2table_record);
            $weight_class = $os2Table['usWeightClass'] ?? null;
        }
        fclose($file);
        return new self($path, $family, $subfamily, $weight_class);
    }
    // // https://learn.microsoft.com/en-us/typography/opentype/spec/otff#table-directory
    // TableDirectory
    private static function read_table_directory($file): array
    {
        return [
            // 0x00010000 or 0x4F54544F ('OTTO')
            'sfntVersion' => self::uint32($file),
            // Number of tables.
            'numTables' => self::uint16($file),
            // Maximum power of 2 less than or equal to numTables, times 16.
            'searchRange' => self::uint16($file),
            // Log2 of the maximum power of 2 less than or equal to numTables.
            'entrySelector' => self::uint16($file),
            // numTables times 16, minus searchRange
            'rangeShift' => self::uint16($file),
        ];
    }
    /**
     * Perform a linear search over the table directory to locate the table record for the given table tag.
     * At the end, the file pointer will be positioned at the beginning of the table record, if found; otherwise,
     * the position will be unchanged.
     *
     * @param $file resource The file.
     * @param $tableDirectory array The table directory.
     * @param $tag string The tag to locate.
     * @return bool True if the table record was found and the file pointer changed; otherwise, false.
     */
    private static function locate_table_record($file, array $table_directory, string $tag): bool
    {
        for ($i = 0; $i < $table_directory['numTables']; ++$i) {
            $pos = 12 + $i * 16;
            if (self::string_at($file, $pos, 4) === $tag) {
                fseek($file, $pos);
                return true;
            }
        }
        return false;
    }
    // https://learn.microsoft.com/en-us/typography/opentype/spec/otff#table-directory
    // TableRecord
    private static function read_table_record($file): array
    {
        return [
            // Table identifier.
            'tag' => self::string($file, 4),
            // Checksum for this table.
            'checkSum' => self::uint32($file),
            // Offset from beginning of font file.
            'offset' => self::uint32($file),
            // Length of this table.
            'length' => self::uint32($file),
        ];
    }
    // https://learn.microsoft.com/en-us/typography/opentype/spec/name
    private static function read_naming_table($file, array $record): array
    {
        fseek($file, $record['offset']);
        // https://learn.microsoft.com/en-us/typography/opentype/spec/name#naming-table-version-0
        // Table version number (0 or 1).
        self::uint16($file);
        // Number of name records.
        $count = self::uint16($file);
        // Offset to start of string storage (from start of table).
        $storage_offset = self::uint16($file);
        $names = [];
        for ($i = 0; $i < $count; ++$i) {
            $name_record = self::read_name_record($file);
            $string = self::string_at($file, $record['offset'] + $storage_offset + $name_record['stringOffset'], $name_record['stringLength']);
            $id = $name_record['nameID'];
            $names[$id] = self::decode_string($string, $name_record['platformID']);
        }
        // Note: Table version 1 has additional fields after the name records, but we don't need those.
        // See: https://learn.microsoft.com/en-us/typography/opentype/spec/name#naming-table-version-1
        return $names;
    }
    // https://learn.microsoft.com/en-us/typography/opentype/spec/name#name-records
    // NameRecord
    private static function read_name_record($file): array
    {
        return [
            // Platform ID.
            'platformID' => self::uint16($file),
            // Platform-specific encoding ID.
            'encodingID' => self::uint16($file),
            // Language ID.
            'languageID' => self::uint16($file),
            // Name ID.
            'nameID' => self::uint16($file),
            // String length (in bytes).
            'stringLength' => self::uint16($file),
            // String offset from start of storage area (in bytes).
            'stringOffset' => self::uint16($file),
        ];
    }
    // https://learn.microsoft.com/en-us/typography/opentype/spec/os2
    private static function read_os2table($file, array $record): array
    {
        fseek($file, $record['offset']);
        // Note: There are many fields in the OS/2 table, even in version 0, but we only need a few.
        return [
            // OS/2 table version number.
            'version' => self::uint16($file),
            // Average weighted escapement.
            'xAvgCharWidth' => self::int16($file),
            // Weight class.
            'usWeightClass' => self::uint16($file),
            // Width class.
            'usWidthClass' => self::uint16($file),
        ];
    }
    private static function uint16($file): int
    {
        $bytes = fread($file, 2);
        return (ord($bytes[0]) << 8) + ord($bytes[1]);
    }
    private static function int16($file): int
    {
        $bytes = fread($file, 2);
        $value = (ord($bytes[0]) << 8) + ord($bytes[1]);
        return $value < 0x8000 ? $value : $value - 0x10000;
    }
    private static function uint32($file): int
    {
        $bytes = fread($file, 4);
        return (ord($bytes[0]) << 24) + (ord($bytes[1]) << 16) + (ord($bytes[2]) << 8) + ord($bytes[3]);
    }
    private static function string($file, int $length): string
    {
        return fread($file, $length);
    }
    private static function string_at($file, int $offset, int $length): string
    {
        $pos = ftell($file);
        fseek($file, $offset);
        $string = self::string($file, $length);
        fseek($file, $pos);
        return $string;
    }
    // https://learn.microsoft.com/en-us/typography/opentype/spec/name#platform-encoding-and-language
    private static function decode_string(string $string, int $platform_id): string
    {
        // https://learn.microsoft.com/en-us/typography/opentype/spec/name#platform-ids
        switch ($platform_id) {
            case self::PLATFORM_ID_UNICODE:
            case self::PLATFORM_ID_WINDOWS:
                // Strings for the Unicode platform must be encoded in UTF-16BE.
                return mb_convert_encoding($string, 'UTF-8', 'UTF-16BE');
            case self::PLATFORM_ID_MACINTOSH:
            default:
                // Strings for the Macintosh platform (platform ID 1) use platform-specific single- or double-byte
                // encodings according to the specified encoding ID for a given name record.
                // TODO: implement
                return $string;
        }
    }
}