<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $brand }} report for {{ $audit->domain }}</title>
<style>
    @page {
        margin: 26mm 15mm 20mm 15mm;
    }

    * { margin: 0; padding: 0; }

    body {
        font-family: 'Source Sans 3', 'DejaVu Sans', sans-serif;
        font-size: 10pt;
        color: #16202b;
        line-height: 1.45;
        -webkit-print-color-adjust: exact;
    }

    table { border-collapse: collapse; width: 100%; }
    td, th { vertical-align: top; }

    .cover-head {
        background-color: #0c2a43;
        padding: 16pt 18pt 18pt 18pt;
    }
    .cover-head .brand {
        font-family: 'Tenor Sans', 'Source Sans 3', sans-serif;
        font-size: 15pt;
        color: #ffffff;
        letter-spacing: 1.2pt;
    }
    .cover-head .brand-mark { color: #7fb2d9; }
    .cover-head .kicker {
        font-size: 7.5pt;
        letter-spacing: 1.6pt;
        text-transform: uppercase;
        color: #9fc3de;
        padding-bottom: 10pt;
    }
    .cover-head h1 {
        font-family: 'Tenor Sans', 'Source Sans 3', sans-serif;
        font-size: 25pt;
        line-height: 1.16;
        color: #ffffff;
        font-weight: normal;
    }
    .cover-head .domain {
        font-size: 10pt;
        color: #cfe0ee;
        padding-top: 6pt;
    }
    .cover-meta {
        background-color: #e8eef3;
        padding: 9pt 18pt;
        font-size: 8.5pt;
        color: #31506d;
    }
    .cover-meta td { padding-right: 18pt; }
    .cover-meta .label {
        letter-spacing: 0.8pt;
        text-transform: uppercase;
        font-size: 7pt;
        color: #5b7a94;
    }

    h2.section {
        font-family: 'Tenor Sans', 'Source Sans 3', sans-serif;
        font-size: 13pt;
        font-weight: normal;
        color: #12395a;
        padding-bottom: 5pt;
        margin-top: 20pt;
        border-bottom: 1pt solid #12395a;
        page-break-after: avoid;
    }
    h3.sub {
        font-size: 9.5pt;
        color: #12395a;
        padding: 9pt 0 4pt 0;
        page-break-after: avoid;
    }
    .intro {
        font-size: 9.5pt;
        color: #46586a;
        padding-bottom: 8pt;
    }

    .score-band { background-color: #f5f4f0; padding: 16pt 18pt; }
    .score-band .prob {
        font-family: 'Tenor Sans', 'Source Sans 3', sans-serif;
        font-size: 46pt;
        line-height: 1;
        color: #12395a;
    }
    .score-band .prob-suffix { font-size: 20pt; color: #5b7a94; }
    .score-band .prob-label {
        font-size: 7.5pt;
        letter-spacing: 1.3pt;
        text-transform: uppercase;
        color: #5b7a94;
        padding-bottom: 3pt;
    }
    .score-band .verdict {
        font-size: 13pt;
        color: #16202b;
        padding-top: 6pt;
    }
    .score-band .note { font-size: 8.5pt; color: #46586a; padding-top: 3pt; }

    .gauge { width: 110pt; border-collapse: collapse; }
    .gauge td { height: 7pt; padding: 0; font-size: 1pt; line-height: 1pt; }
    .gauge-wide { width: 200pt; }
    .gauge-wide td { height: 8pt; }

    .badge {
        display: inline-block;
        padding: 2.5pt 7pt;
        font-size: 7.5pt;
        letter-spacing: 0.8pt;
        text-transform: uppercase;
        color: #ffffff;
    }
    .badge.pass { background-color: #1f6f4a; }
    .badge.warn { background-color: #9a6512; }
    .badge.fail { background-color: #a53a34; }
    .badge.info { background-color: #3d5a73; }

    .cat-table th {
        background-color: #12395a;
        color: #ffffff;
        font-size: 7.5pt;
        letter-spacing: 0.9pt;
        text-transform: uppercase;
        text-align: left;
        padding: 6pt 8pt;
        font-weight: normal;
    }
    .cat-table td {
        padding: 6pt 8pt;
        border-bottom: 0.5pt solid #e2e0da;
        font-size: 9pt;
    }
    .cat-table td.num { text-align: right; width: 42pt; font-family: 'IBM Plex Mono', monospace; font-size: 8.5pt; }
    .cat-table td.weight { text-align: right; width: 52pt; color: #6b7c8c; font-size: 8pt; }
    .cat-table tr.alt td { background-color: #f7f8f9; }

    .finding {
        border-left: 2.5pt solid #a53a34;
        padding: 0 0 0 9pt;
        margin-bottom: 9pt;
        page-break-inside: avoid;
    }
    .finding.warn { border-left-color: #9a6512; }
    .finding.pass { border-left-color: #1f6f4a; }
    .finding.info { border-left-color: #3d5a73; }
    .finding .title { font-size: 10pt; color: #16202b; }
    .finding .meta { font-size: 7.5pt; color: #6b7c8c; letter-spacing: 0.6pt; text-transform: uppercase; padding: 1.5pt 0 2.5pt 0; }
    .finding .detail { font-size: 9pt; color: #3a4a59; }
    .finding .fix {
        font-size: 8.5pt;
        color: #12395a;
        background-color: #eef3f7;
        padding: 4pt 6pt;
        margin-top: 4pt;
    }
    .finding .fix strong { color: #0c2a43; }

    .net {
        border: 0.75pt solid #e2e0da;
        margin-bottom: 8pt;
        page-break-inside: avoid;
    }
    .net .head { background-color: #f5f4f0; padding: 6pt 9pt; }
    .net .head .name { font-size: 10pt; color: #12395a; }
    .net .head .state { text-align: right; width: 110pt; }
    .net .body { padding: 7pt 9pt; font-size: 8.5pt; color: #3a4a59; }
    .net ul { margin: 0 0 0 11pt; }
    .net li { padding-bottom: 2pt; }

    .roadmap-step {
        page-break-inside: avoid;
        margin-bottom: 10pt;
    }
    .roadmap-step .head {
        background-color: #e8eef3;
        padding: 5pt 8pt;
        font-size: 9.5pt;
        color: #0c2a43;
    }
    .roadmap-step .num {
        display: inline-block;
        width: 16pt;
        height: 16pt;
        line-height: 16pt;
        text-align: center;
        background-color: #12395a;
        color: #ffffff;
        font-size: 8.5pt;
        margin-right: 6pt;
        font-family: 'IBM Plex Mono', monospace;
    }
    .roadmap-step .body { padding: 6pt 8pt 0 8pt; font-size: 9pt; color: #3a4a59; }

    .callout { background-color: #f5f4f0; padding: 9pt 11pt; margin-bottom: 7pt; page-break-inside: avoid; }
    .callout .t { font-size: 8pt; letter-spacing: 1pt; text-transform: uppercase; color: #5b7a94; padding-bottom: 3pt; }
    .callout ul { margin: 0 0 0 12pt; font-size: 9pt; color: #3a4a59; }
    .callout li { padding-bottom: 2pt; }

    .analysis-block { margin-bottom: 10pt; page-break-inside: avoid; }
    .analysis-block .h {
        font-size: 9.5pt;
        color: #12395a;
        border-bottom: 0.5pt solid #e2e0da;
        padding-bottom: 3pt;
        margin-bottom: 4pt;
    }
    .analysis-block .t { font-size: 9pt; color: #3a4a59; }

    .footer-note {
        margin-top: 22pt;
        border-top: 0.75pt solid #e2e0da;
        padding-top: 7pt;
        font-size: 7.5pt;
        color: #6b7c8c;
    }
    .mono { font-family: 'IBM Plex Mono', monospace; }
</style>
</head>
<body>

<table class="cover-head" cellspacing="0" cellpadding="0">
    <tr>
        <td>
            <div class="kicker">Ad network readiness report</div>
            <div class="brand"><span class="brand-mark">&#9679;</span> {{ $brand }}</div>
        </td>
    </tr>
    <tr>
        <td style="padding-top: 14pt;">
            <h1>{{ $audit->domain ?: $audit->url }}</h1>
            <div class="domain">{{ $audit->url }}</div>
        </td>
    </tr>
</table>

<table class="cover-meta" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width: 22%;">
            <div class="label">Prepared</div>
            <div>{{ $generatedAt->format('j M Y, H:i') }}</div>
        </td>
        <td style="width: 20%;">
            <div class="label">Audit depth</div>
            <div>{{ $modeLabel }}</div>
        </td>
        <td style="width: 22%;">
            <div class="label">Pages reviewed</div>
            <div>{{ $audit->pages_crawled ?: 0 }}</div>
        </td>
        <td style="width: 18%;">
            <div class="label">Findings</div>
            <div>{{ $audit->findings_count ?: 0 }}</div>
        </td>
        <td style="width: 18%;">
            <div class="label">Report ID</div>
            <div class="mono">#{{ $audit->id }}</div>
        </td>
    </tr>
</table>

<table class="score-band" cellspacing="0" cellpadding="0" style="margin-top: 14pt;">
    <tr>
        <td style="width: 34%;">
            <div class="prob-label">Approval probability</div>
            <div class="prob">{{ $audit->probability ?? 0 }}<span class="prob-suffix">%</span></div>
            <div class="note mono">Readiness score {{ $audit->score ?? 0 }} / 100</div>
        </td>
        <td style="width: 66%;">
            <div class="prob-label">Verdict</div>
            <div class="verdict">{{ $verdictLabel }}</div>
            <div style="margin-top: 8pt;">
                @include('reports.partials.gauge', ['value' => $audit->probability ?? 0, 'cells' => 40, 'class' => 'gauge gauge-wide'])
            </div>
            <div class="note" style="padding-top: 6pt;">
                @if ($summary)
                    {{ \Illuminate\Support\Str::limit($summary, 260) }}
                @else
                    This score blends a deterministic rule audit of {{ $audit->pages_crawled ?: 0 }} crawled pages with a model review of the same evidence.
                @endif
            </div>
        </td>
    </tr>
</table>

<h2 class="section">1. Readiness by area</h2>
<p class="intro">Each area is scored from 0 to 100 and weighted by how heavily Google's reviewers weigh it during the approval review.</p>
<table class="cat-table" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th>Area</th>
            <th style="width: 110pt;">Signal</th>
            <th style="width: 42pt; text-align: right;">Score</th>
            <th style="width: 52pt; text-align: right;">Weight</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($categories as $key => $category)
            <tr class="{{ $loop->index % 2 ? 'alt' : '' }}">
                <td>{{ $category['label'] }}</td>
                <td>
                    @include('reports.partials.gauge', ['value' => $category['score'], 'cells' => 20, 'class' => 'gauge'])
                </td>
                <td class="num">{{ (int) round($category['score']) }}</td>
                <td class="weight">{{ $category['weight'] }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2 class="section">2. Blockers to fix first</h2>
<p class="intro">These are the findings that will most likely decide the outcome of a first application. Fix them in order and re-run the audit.</p>

@forelse ($critical as $finding)
    <div class="finding">
        <div class="title">{{ $finding->title }}</div>
        <div class="meta">{{ $finding->categoryLabel() }} &middot; must fix</div>
        @if ($finding->detail)
            <div class="detail">{{ $finding->detail }}</div>
        @endif
        @if ($finding->recommendation)
            <div class="fix"><strong>How to fix it.</strong> {{ $finding->recommendation }}</div>
        @endif
    </div>
@empty
    <div class="callout">
        <div class="t">No blocking issues</div>
        <ul>
            <li>No critical policy or technical blockers were detected on this pass.</li>
        </ul>
    </div>
@endforelse

@if (count($criticalFixes) > 0)
    <div class="callout">
        <div class="t">Critical fix list</div>
        <ul>
            @foreach ($criticalFixes as $fix)
                <li>{{ is_array($fix) ? ($fix['title'] ?? $fix['description'] ?? '') : $fix }}</li>
            @endforeach
        </ul>
    </div>
@endif

<h2 class="section">3. What needs work</h2>
<p class="intro">Non blocking issues that still cost points with reviewers.</p>

@forelse ($warnings as $finding)
    <div class="finding warn">
        <div class="title">{{ $finding->title }}</div>
        <div class="meta">{{ $finding->categoryLabel() }} &middot; needs work</div>
        @if ($finding->detail)
            <div class="detail">{{ $finding->detail }}</div>
        @endif
        @if ($finding->recommendation)
            <div class="fix"><strong>How to fix it.</strong> {{ $finding->recommendation }}</div>
        @endif
    </div>
@empty
    <div class="callout">
        <div class="t">Clean sheet</div>
        <ul>
            <li>No warnings were raised on this pass.</li>
        </ul>
    </div>
@endforelse

<h2 class="section">4. What is already working</h2>
<p class="intro">Keep these areas intact. Reviewers notice when a site changes shape between applications.</p>

@forelse ($passes as $finding)
    <div class="finding pass">
        <div class="title">{{ $finding->title }}</div>
        @if ($finding->detail)
            <div class="detail">{{ $finding->detail }}</div>
        @endif
    </div>
@empty
    <div class="callout">
        <div class="t">Nothing confirmed yet</div>
        <ul>
            <li>This pass did not record any confirmed strengths.</li>
        </ul>
    </div>
@endforelse

@if ($notes->isNotEmpty())
    <h3 class="sub">Additional notes</h3>
    @foreach ($notes as $finding)
        <div class="finding info">
            <div class="title">{{ $finding->title }}</div>
            @if ($finding->detail)
                <div class="detail">{{ $finding->detail }}</div>
            @endif
        </div>
    @endforeach
@endif

<h2 class="section">5. Network readiness</h2>
<p class="intro">The same site is judged against a different bar by every network. This is where you stand with each of them today.</p>

@foreach ($networks as $network)
    <div class="net">
        <table class="head" cellspacing="0" cellpadding="0">
            <tr>
                <td class="name">{{ $network['label'] }}</td>
                <td class="state">
                    <span class="badge {{ $network['ready'] ? 'pass' : 'warn' }}">{{ $network['ready'] ? 'Ready' : 'Not ready' }}</span>
                    <span style="font-size: 8pt; color: #5b7a94;">&nbsp;&nbsp;{{ $network['score'] }}% of requirements met</span>
                </td>
            </tr>
        </table>
        <div class="body">
            <ul>
                @forelse ($network['notes'] as $note)
                    <li>{{ is_array($note) ? ($note['text'] ?? $note['note'] ?? '') : $note }}</li>
                @empty
                    @foreach ($network['requirements'] as $requirement)
                        <li>{{ $requirement }}</li>
                    @endforeach
                @endforelse
            </ul>
        </div>
    </div>
@endforeach

@if ($sections)
    <h2 class="section">6. Detailed analysis</h2>
    <p class="intro">A section by section read of the evidence that was collected and reviewed.</p>
    @foreach ($sections as $section)
        <div class="analysis-block">
            <div class="h">{{ $section['label'] }}</div>
            <div class="t">{{ $section['text'] }}</div>
        </div>
    @endforeach
@endif

<h2 class="section">{{ $sections ? '7' : '6' }}. First attempt roadmap</h2>
<p class="intro">Follow these steps in order. The order matters because each step removes a reason for a reviewer to reject the site.</p>

@forelse ($roadmap as $index => $step)
    <div class="roadmap-step">
        <table class="head" cellspacing="0" cellpadding="0">
            <tr>
                <td><span class="num">{{ $index + 1 }}</span>{{ $step['title'] }}</td>
            </tr>
        </table>
        <div class="body">{{ $step['detail'] }}</div>
    </div>
@empty
    <div class="callout">
        <div class="t">Roadmap</div>
        <ul>
            <li>Clear every blocker listed in section 2, then re-run the audit to generate the detailed roadmap.</li>
        </ul>
    </div>
@endforelse

@if ($strengths || $risks)
    <h2 class="section">{{ $sections ? '8' : '7' }}. Strengths and risks</h2>
    @if ($strengths)
        <div class="callout">
            <div class="t">Strengths</div>
            <ul>
                @foreach ($strengths as $strength)
                    <li>{{ is_array($strength) ? ($strength['text'] ?? $strength['title'] ?? '') : $strength }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($risks)
        <div class="callout">
            <div class="t">Risks</div>
            <ul>
                @foreach ($risks as $risk)
                    <li>{{ is_array($risk) ? ($risk['text'] ?? $risk['title'] ?? '') : $risk }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endif

<div class="footer-note">
    <table cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 60%;">
                {{ $brand }} &middot; report #{{ $audit->id }} for {{ $audit->domain }} &middot; generated {{ $generatedAt->format('j M Y') }}
            </td>
            <td style="text-align: right; width: 40%;">
                @if ($audit->model)
                    reviewed by <span class="mono">{{ $audit->model }}</span>
                @endif
            </td>
        </tr>
    </table>
</div>

<script type="text/php">
    $font = $fontMetrics->getFont('IBM Plex Mono', 'normal');
    $label = '{PAGE_NUM} of {PAGE_COUNT}';
    $width = $fontMetrics->getTextWidth($label, $font, 7.5);
    $pdf->page_text($pdf->get_width() - 42 - $width, $pdf->get_height() - 32, $label, $font, 7.5, [0.42, 0.49, 0.55]);
    $pdf->page_text(42, $pdf->get_height() - 32, '{{ $brand }} readiness report for {{ $audit->domain }}', $font, 7.5, [0.42, 0.49, 0.55]);
</script>

</body>
</html>
