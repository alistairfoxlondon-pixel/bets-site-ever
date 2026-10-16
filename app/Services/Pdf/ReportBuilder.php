<?php

namespace App\Services\Pdf;

use App\Models\Audit;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReportBuilder
{
    public function __construct(private readonly PdfFonts $fonts = new PdfFonts)
    {
    }

    /**
     * Render the audit report and return the raw PDF bytes.
     */
    public function render(Audit $audit): string
    {
        $html = view('reports.audit', $this->payload($audit))->render();

        $dompdf = new Dompdf($this->fonts->options());
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper(config('adready.pdf.paper', 'a4'), config('adready.pdf.orientation', 'portrait'));
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Store the report on disk and remember the path on the audit record.
     */
    public function save(Audit $audit): string
    {
        $bytes = $this->render($audit);

        $directory = trim((string) config('adready.pdf.path', 'reports'), '/');
        $disk = Storage::disk((string) config('adready.pdf.disk', 'local'));
        $filename = $directory.'/audit-'.$audit->id.'-'.now()->format('Ymd-His').'.pdf';

        $disk->put($filename, $bytes);

        $meta = $audit->meta ?? [];
        $meta['pdf_path'] = $filename;
        $audit->forceFill(['meta' => $meta])->save();

        return $filename;
    }

    public function download(Audit $audit): Response
    {
        $bytes = $this->render($audit);
        $filename = 'adready-audit-'.($audit->domain ?: 'report').'-'.$audit->id.'.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($bytes),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * Inline view of the report for the browser preview.
     */
    public function inline(Audit $audit): Response
    {
        $bytes = $this->render($audit);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="adready-audit-'.$audit->id.'.pdf"',
            'Content-Length' => (string) strlen($bytes),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Audit $audit): array
    {
        $audit->loadMissing('findings');

        $findings = $audit->findings->groupBy('severity');

        $categories = $audit->categoryScores();

        $networks = (array) data_get($audit->meta, 'network_readiness', []);

        $networksOut = [];

        foreach (config('adready.networks') as $key => $meta) {
            $entry = $networks[$key] ?? null;

            $networksOut[] = [
                'key' => $key,
                'label' => $meta['label'],
                'ready' => (bool) ($entry['ready'] ?? false),
                'score' => (int) ($entry['score'] ?? 0),
                'notes' => array_values((array) ($entry['notes'] ?? [])),
                'requirements' => $meta['requirements'],
            ];
        }

        $analysis = (array) data_get($audit->meta, 'analysis', []);

        $analysisSections = [
            ['key' => 'website_quality', 'label' => 'Website quality'],
            ['key' => 'content_quality', 'label' => 'Content quality'],
            ['key' => 'traffic_quality', 'label' => 'Traffic quality'],
            ['key' => 'policy_risks', 'label' => 'Policy risks'],
            ['key' => 'trust_eeat', 'label' => 'Trust and E-E-A-T'],
            ['key' => 'seo_indexing', 'label' => 'SEO and indexing'],
        ];

        $sections = [];

        foreach ($analysisSections as $section) {
            $text = trim((string) ($analysis[$section['key']] ?? ''));

            if ($text !== '') {
                $sections[] = ['label' => $section['label'], 'text' => $text];
            }
        }

        $roadmap = array_values((array) data_get($audit->meta, 'roadmap', []));

        $roadmapOut = [];

        foreach ($roadmap as $index => $step) {
            if (is_array($step)) {
                $title = (string) ($step['title'] ?? $step['step'] ?? ('Step '.($index + 1)));
                $detail = (string) ($step['description'] ?? $step['detail'] ?? $step['action'] ?? '');
            } else {
                $title = 'Step '.($index + 1);
                $detail = (string) $step;
            }

            $roadmapOut[] = ['title' => $title, 'detail' => $detail];
        }

        return [
            'audit' => $audit,
            'brand' => config('app.name'),
            'generatedAt' => now(),
            'categories' => $categories,
            'critical' => $findings->get('critical', collect()),
            'warnings' => $findings->get('warning', collect()),
            'passes' => $findings->get('pass', collect()),
            'notes' => $findings->get('info', collect()),
            'networks' => $networksOut,
            'sections' => $sections,
            'roadmap' => $roadmapOut,
            'strengths' => array_values((array) data_get($audit->meta, 'strengths', [])),
            'risks' => array_values((array) data_get($audit->meta, 'risks', [])),
            'criticalFixes' => array_values((array) data_get($audit->meta, 'critical_fixes', [])),
            'summary' => data_get($audit->meta, 'summary'),
            'verdictLabel' => $audit->verdictLabel(),
            'verdictKey' => $audit->verdict ?? 'high_risk',
            'modeLabel' => config('adready.modes.'.$audit->mode.'.label', ucfirst((string) $audit->mode)),
        ];
    }
}
