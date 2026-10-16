<?php

namespace App\Services\Pdf;

use Dompdf\Options;
use FontLib\AdobeFontMetrics;
use FontLib\Font;
use Throwable;

/**
 * Registers the brand fonts with the PDF renderer. The TTF files live in
 * resources/fonts so the report uses the same typography as the site.
 *
 * Dompdf reads font metrics from an Adobe font metrics file that sits next
 * to the font, so both are prepared in the storage font directory the first
 * time a report is rendered.
 */
class PdfFonts
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function families(): array
    {
        return [
            'source sans 3' => [
                'normal' => 'SourceSans3-Regular.ttf',
                'bold' => 'SourceSans3-Bold.ttf',
                'italic' => 'SourceSans3-Regular.ttf',
                'bold_italic' => 'SourceSans3-Bold.ttf',
            ],
            'tenor sans' => [
                'normal' => 'TenorSans.ttf',
                'bold' => 'TenorSans.ttf',
                'italic' => 'TenorSans.ttf',
                'bold_italic' => 'TenorSans.ttf',
            ],
            'ibm plex mono' => [
                'normal' => 'IBMPlexMono-Regular.ttf',
                'bold' => 'IBMPlexMono-Medium.ttf',
                'italic' => 'IBMPlexMono-Regular.ttf',
                'bold_italic' => 'IBMPlexMono-Medium.ttf',
            ],
        ];
    }

    public static function options(): Options
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'Source Sans 3');
        $options->set('defaultPaperSize', 'a4');
        $options->set('fontDir', self::fontDir());
        $options->set('fontCache', self::fontDir());
        $options->setChroot([base_path(), storage_path()]);
        $options->setLogOutputFile(storage_path('logs/dompdf.log'));

        return $options;
    }

    /**
     * Prepare the font directory: copy the brand fonts in, build the metrics
     * files and write the family index dompdf reads on boot.
     */
    public static function fontDir(): string
    {
        $dir = storage_path('fonts');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        self::writeFontIndex($dir);

        return $dir;
    }

    /**
     * Write the font index the renderer reads on boot.
     */
    public static function writeFontIndex(string $dir): void
    {
        $index = [];
        $sourceDir = resource_path('fonts');

        foreach (self::families() as $family => $variants) {
            foreach ($variants as $style => $file) {
                $source = $sourceDir.'/'.$file;

                if (! is_file($source)) {
                    continue;
                }

                $name = pathinfo($file, PATHINFO_FILENAME);
                $target = $dir.'/'.$name.'.ttf';

                if (! is_file($target) || filemtime($target) < filemtime($source)) {
                    copy($source, $target);
                }

                self::ensureMetrics($target);

                if (is_file($dir.'/'.$name.'.ufm')) {
                    // Dompdf expects the family entry without the file
                    // extension so it can find the metrics file itself.
                    $index[$family][$style] = $dir.'/'.$name;
                }
            }
        }

        file_put_contents(
            $dir.'/installed-fonts.json',
            json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Build the Adobe font metrics file for a TrueType font when it is
     * missing or older than the font itself.
     */
    public static function ensureMetrics(string $ttfPath): bool
    {
        if (! is_file($ttfPath)) {
            return false;
        }

        $ufm = substr($ttfPath, 0, -4).'.ufm';

        if (is_file($ufm) && filemtime($ufm) >= filemtime($ttfPath)) {
            return true;
        }

        try {
            $font = Font::load($ttfPath);

            if (! $font) {
                return false;
            }

            if (method_exists($font, 'parse')) {
                $font->parse();
            }

            $metrics = new AdobeFontMetrics($font);
            $metrics->write($ufm);
        } catch (Throwable) {
            return false;
        }

        return is_file($ufm);
    }
}
