<?php

namespace App\Services\CountryConsole;

/**
 * Minimal Excel (.xlsx) writer - one sheet, bold headers, merged cells, frozen panes, column widths.
 * Uses PHP's ZipArchive only (no extra package). Numbers stay numbers so Excel can add them up.
 */
class SimpleXlsx
{
    private array $rows = [];
    private array $merges = [];
    private array $widths = [];
    private ?array $freeze = null;

    /** Adds a row; returns its 1-based row number. $style: '' | head | title */
    public function row(array $cells, string $style = '', array $cellStyles = []): int
    {
        $this->rows[] = [$cells, $style, $cellStyles];
        return count($this->rows);
    }

    public function merge(int $c1, int $r1, int $c2, int $r2): void { if ($c2 > $c1 || $r2 > $r1) $this->merges[] = self::ref($c1, $r1) . ':' . self::ref($c2, $r2); }
    public function widths(array $w): void { $this->widths = $w; }
    public function freeze(int $cols, int $rows): void { $this->freeze = [$cols, $rows]; }

    private static function col(int $i): string { $s = ''; $i++; while ($i > 0) { $m = ($i - 1) % 26; $s = chr(65 + $m) . $s; $i = intdiv($i - $m, 26); } return $s; }
    private static function ref(int $c, int $r): string { return self::col($c) . $r; }
    private static function x(string $s): string { return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s), ENT_XML1 | ENT_QUOTES, 'UTF-8'); }

    public function build(string $sheetName = 'Sheet1'): string
    {
        $style = ['' => 0, 'head' => 1, 'title' => 2, 'zero' => 3, 'low' => 4];
        $xml = '';
        foreach ($this->rows as $ri => [$cells, $st, $cs]) {
            $r = $ri + 1; $xml .= '<row r="' . $r . '">';
            foreach (array_values($cells) as $ci => $v) {
                $cst = $cs[$ci] ?? $st;
                if ($v === null || $v === '') { if ($cst !== '') $xml .= '<c r="' . self::ref($ci, $r) . '" s="' . ($style[$cst] ?? 0) . '"/>'; continue; }
                $s = $style[$cst] ?? 0; $a = ' r="' . self::ref($ci, $r) . '"' . ($s ? ' s="' . $s . '"' : '');
                $xml .= (is_int($v) || is_float($v)) ? '<c' . $a . '><v>' . (is_float($v) ? rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.') : $v) . '</v></c>'
                    : '<c' . $a . ' t="inlineStr"><is><t xml:space="preserve">' . self::x((string) $v) . '</t></is></c>';
            }
            $xml .= '</row>';
        }
        $view = '<sheetViews><sheetView workbookViewId="0">' . ($this->freeze ? '<pane xSplit="' . $this->freeze[0] . '" ySplit="' . $this->freeze[1] . '" topLeftCell="' . self::ref($this->freeze[0], $this->freeze[1] + 1) . '" activePane="bottomRight" state="frozen"/>' : '') . '</sheetView></sheetViews>';
        $cols = '';
        if ($this->widths) { $cols = '<cols>'; foreach ($this->widths as $i => $w) $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>'; $cols .= '<col min="' . (count($this->widths) + 1) . '" max="400" width="11" customWidth="1"/></cols>'; }
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . $view . $cols . '<sheetData>' . $xml . '</sheetData>' . ($this->merges ? '<mergeCells count="' . count($this->merges) . '">' . implode('', array_map(fn ($m) => '<mergeCell ref="' . $m . '"/>', $this->merges)) . '</mergeCells>' : '') . '</worksheet>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="14"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFB91C1C"/><name val="Calibri"/></font></fonts>'
            . '<fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFDEEE8"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFAD4D4"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFF0C2"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border/><border><left style="thin"><color rgb="FFD9D9D9"/></left><right style="thin"><color rgb="FFD9D9D9"/></right><top style="thin"><color rgb="FFD9D9D9"/></top><bottom style="thin"><color rgb="FFD9D9D9"/></bottom></border></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="5"><xf fontId="0" fillId="0" borderId="0"/><xf fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf fontId="2" fillId="0" borderId="0" applyFont="1"/><xf fontId="3" fillId="3" borderId="1" applyFont="1" applyFill="1" applyBorder="1"/><xf fontId="0" fillId="4" borderId="1" applyFill="1" applyBorder="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $z = new \ZipArchive();
        $z->open($tmp, \ZipArchive::OVERWRITE);
        $z->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $z->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $z->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . self::x(mb_substr($sheetName, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $z->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $z->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $z->addFromString('xl/styles.xml', $styles);
        $z->close();
        $bin = file_get_contents($tmp); @unlink($tmp);
        return $bin;
    }
}
