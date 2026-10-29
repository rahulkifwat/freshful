<?php

namespace App\Services\AdminConsole;

/**
 * Freshful admin console: reads the first sheet of an .xlsx file, or a .csv file, into rows of text.
 * PHP's ZipArchive + SimpleXML only (no extra package). Old .xls files are not read: save them as .xlsx first.
 */
class AdminSheet
{
    public static function read(string $path, string $ext, int $maxRows = 5000): array
    {
        $ext = strtolower($ext);
        if ($ext === 'csv' || $ext === 'txt') return self::csv($path, $maxRows);
        if ($ext === 'xlsx') return self::xlsx($path, $maxRows);
        throw new \RuntimeException('Save the file as Excel (.xlsx) or CSV and upload it again.');
    }

    private static function csv(string $path, int $max): array
    {
        $rows = []; $fh = fopen($path, 'r');
        if (!$fh) throw new \RuntimeException('Could not open the file.');
        $first = fgets($fh); rewind($fh);
        $delim = substr_count((string) $first, ';') > substr_count((string) $first, ',') ? ';' : ',';
        while (($r = fgetcsv($fh, 0, $delim)) !== false) {
            if (count($rows) === 0 && isset($r[0])) $r[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $r[0]);
            $rows[] = array_map(fn ($v) => trim((string) $v), $r);
            if (count($rows) > $max) break;
        }
        fclose($fh);
        return $rows;
    }

    private static function xlsx(string $path, int $max): array
    {
        if (!class_exists(\ZipArchive::class)) throw new \RuntimeException('The server cannot read Excel files. Upload a CSV instead.');
        $z = new \ZipArchive();
        if ($z->open($path) !== true) throw new \RuntimeException('This is not a valid Excel (.xlsx) file.');
        $shared = [];
        $ss = $z->getFromName('xl/sharedStrings.xml');
        if ($ss !== false) {
            $x = @simplexml_load_string($ss, 'SimpleXMLElement', LIBXML_NONET);
            if ($x) foreach ($x->si as $si) {
                if (isset($si->t)) $shared[] = (string) $si->t;
                else { $t = ''; foreach ($si->r as $run) $t .= (string) $run->t; $shared[] = $t; }
            }
        }
        $sheetPath = 'xl/worksheets/sheet1.xml';
        $wb = $z->getFromName('xl/workbook.xml'); $rels = $z->getFromName('xl/_rels/workbook.xml.rels');
        if ($wb !== false && $rels !== false) {
            $w = @simplexml_load_string($wb, 'SimpleXMLElement', LIBXML_NONET); $rl = @simplexml_load_string($rels, 'SimpleXMLElement', LIBXML_NONET);
            if ($w && $rl && isset($w->sheets->sheet[0])) {
                $rid = (string) $w->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                foreach ($rl->Relationship as $r) if ((string) $r['Id'] === $rid) { $t = ltrim((string) $r['Target'], '/'); $sheetPath = str_starts_with($t, 'xl/') ? $t : 'xl/' . $t; }
            }
        }
        $sx = $z->getFromName($sheetPath);
        $z->close();
        if ($sx === false) throw new \RuntimeException('The Excel file has no sheet.');
        $x = @simplexml_load_string($sx, 'SimpleXMLElement', LIBXML_NONET);
        if (!$x) throw new \RuntimeException('The Excel sheet could not be read.');
        $rows = [];
        foreach ($x->sheetData->row as $row) {
            $out = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r']; $col = self::colIndex(preg_replace('/\d+/', '', $ref));
                $t = (string) $c['t']; $v = '';
                if ($t === 's') $v = $shared[(int) $c->v] ?? '';
                elseif ($t === 'inlineStr') $v = (string) ($c->is->t ?? '');
                elseif ($t === 'b') $v = ((string) $c->v) === '1' ? 'TRUE' : 'FALSE';
                else { $v = (string) $c->v; if (is_numeric($v) && str_contains($v, 'E')) $v = rtrim(rtrim(number_format((float) $v, 6, '.', ''), '0'), '.'); }
                while (count($out) < $col) $out[] = '';
                $out[$col] = trim($v);
            }
            $rows[] = $out;
            if (count($rows) > $max) break;
        }
        return $rows;
    }

    private static function colIndex(string $letters): int
    {
        $n = 0; foreach (str_split(strtoupper($letters)) as $ch) $n = $n * 26 + (ord($ch) - 64);
        return max(0, $n - 1);
    }
}
