
<svg viewBox="0 0 760 300" class="w-full h-auto" role="img" aria-label="Crawl, check, report pipeline">
    <defs>
        <marker id="arrowHead" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
            <path d="M 0 0 L 10 5 L 0 10 z" fill="#12395a"/>
        </marker>
    </defs>

    <g>
        <rect x="4" y="20" width="220" height="250" rx="4" fill="#ffffff" stroke="#e2e0da"/>
        <text x="24" y="52" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" letter-spacing="1.4" fill="#8a939f">STEP ONE</text>
        <text x="24" y="78" font-family="Tenor Sans, sans-serif" font-size="19" fill="#12395a">Crawl the site</text>

        <g font-family="Source Sans 3, sans-serif" font-size="10" fill="#46586a">
            <rect x="24" y="96" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="36" y="113">/home</text>
            <circle cx="188" cy="109" r="4" fill="#1f6f4a"/>

            <rect x="24" y="130" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="36" y="147">/articles/…</text>
            <circle cx="188" cy="143" r="4" fill="#1f6f4a"/>

            <rect x="24" y="164" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="36" y="181">/robots.txt</text>
            <circle cx="188" cy="177" r="4" fill="#1f6f4a"/>

            <rect x="24" y="198" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="36" y="215">/sitemap.xml</text>
            <circle cx="188" cy="211" r="4" fill="#8a939f"/>

            <rect x="24" y="232" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="36" y="249">/ads.txt</text>
            <circle cx="188" cy="245" r="4" fill="#8a939f"/>
        </g>
    </g>

    <line x1="232" y1="145" x2="268" y2="145" stroke="#12395a" stroke-width="1.4" marker-end="url(#arrowHead)"/>

    <g>
        <rect x="276" y="20" width="220" height="250" rx="4" fill="#ffffff" stroke="#e2e0da"/>
        <text x="296" y="52" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" letter-spacing="1.4" fill="#8a939f">STEP TWO</text>
        <text x="296" y="78" font-family="Tenor Sans, sans-serif" font-size="19" fill="#12395a">Run the checks</text>

        <g font-family="Source Sans 3, sans-serif" font-size="10" fill="#46586a">
            <rect x="296" y="96" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="308" y="113">Content &amp; policy</text>
            <text x="462" y="113" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8" fill="#8a939f">SAMPLE</text>

            <rect x="296" y="130" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="308" y="147">Publisher identity</text>
            <text x="462" y="147" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8" fill="#8a939f">SAMPLE</text>

            <rect x="296" y="164" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="308" y="181">Discovery &amp; metadata</text>
            <text x="462" y="181" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8" fill="#8a939f">SAMPLE</text>

            <rect x="296" y="198" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="308" y="215">Model review</text>
            <text x="462" y="215" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8" fill="#8a939f">ADVISORY</text>
        </g>

        <rect x="296" y="238" width="180" height="20" rx="2" fill="#e8eef3"/>
        <text x="386" y="252" font-family="IBM Plex Mono, monospace" font-size="8.5" fill="#12395a" text-anchor="middle">Public-page heuristics only</text>
    </g>

    <line x1="504" y1="145" x2="540" y2="145" stroke="#12395a" stroke-width="1.4" marker-end="url(#arrowHead)"/>

    <g>
        <rect x="548" y="20" width="208" height="250" rx="4" fill="#0c2a43"/>
        <text x="568" y="52" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" letter-spacing="1.4" fill="#7fb2d9">STEP THREE</text>
        <text x="568" y="78" font-family="Tenor Sans, sans-serif" font-size="19" fill="#ffffff">Write the report</text>

        <rect x="568" y="96" width="168" height="58" rx="2" fill="#12395a" stroke="#31597d"/>
        <text x="582" y="118" font-family="Source Sans 3, sans-serif" font-size="8.5" font-weight="700" letter-spacing=".8" fill="#9fc3de">PUBLIC-SITE INDEX</text>
        <text x="582" y="144" font-family="Tenor Sans, sans-serif" font-size="25" fill="#ffffff">64/100</text>
        <text x="668" y="144" font-family="Source Sans 3, sans-serif" font-size="8" fill="#9fc3de">sample</text>

        <rect x="568" y="164" width="168" height="44" rx="2" fill="#12395a" stroke="#31597d"/>
        <circle cx="582" cy="178" r="3" fill="#a53a34"/>
        <text x="592" y="181" font-family="Source Sans 3, sans-serif" font-size="9" fill="#e6eef5">Priority findings</text>
        <circle cx="582" cy="196" r="3" fill="#9a6512"/>
        <text x="592" y="199" font-family="Source Sans 3, sans-serif" font-size="9" fill="#e6eef5">Other observations</text>

        <rect x="568" y="218" width="168" height="40" rx="2" fill="#f5f4f0"/>
        <text x="652" y="236" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" fill="#12395a" text-anchor="middle">ROADMAP</text>
        <text x="652" y="249" font-family="Source Sans 3, sans-serif" font-size="8.5" fill="#5b6775" text-anchor="middle">7 ordered steps</text>
    </g>

    <text x="380" y="292" font-family="Source Sans 3, sans-serif" font-size="10" fill="#8a939f" text-anchor="middle">Rule findings cite sampled evidence; model observations need human review.</text>
</svg>
