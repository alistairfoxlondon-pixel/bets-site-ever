#!/usr/bin/env python3
"""Write the hand drawn SVG illustrations used on the marketing pages."""
import os

ROOT = "/home/user/bets-site-ever/resources/views/partials/visuals"
os.makedirs(ROOT, exist_ok=True)

FILES = {}

FILES['audit-console.blade.php'] = r'''
<svg viewBox="0 0 560 430" class="w-full h-auto" role="img" aria-label="Audit console showing a site score and findings">
    <rect x="8" y="10" width="544" height="404" rx="4" fill="#ffffff" stroke="#cfcbc2"/>
    <rect x="8" y="10" width="544" height="34" rx="4" fill="#f5f4f0"/>
    <rect x="8" y="34" width="544" height="10" fill="#f5f4f0"/>
    <line x1="8" y1="44" x2="552" y2="44" stroke="#e2e0da"/>
    <circle cx="28" cy="27" r="4" fill="none" stroke="#8a939f"/>
    <circle cx="44" cy="27" r="4" fill="none" stroke="#8a939f"/>
    <circle cx="60" cy="27" r="4" fill="none" stroke="#8a939f"/>
    <rect x="80" y="17" width="300" height="20" rx="3" fill="#ffffff" stroke="#e2e0da"/>
    <circle cx="92" cy="27" r="3.5" fill="none" stroke="#1f6f4a"/>
    <text x="104" y="31" font-family="IBM Plex Mono, monospace" font-size="10" fill="#5b6775">example.com</text>
    <rect x="470" y="17" width="62" height="20" rx="3" fill="#12395a"/>
    <text x="501" y="31" font-family="Source Sans 3, sans-serif" font-size="9.5" font-weight="700" fill="#ffffff" text-anchor="middle">AUDIT</text>

    <rect x="8" y="44" width="150" height="370" fill="#faf9f7"/>
    <line x1="158" y1="44" x2="158" y2="414" stroke="#e2e0da"/>

    <text x="24" y="68" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" letter-spacing="1.4" fill="#8a939f">AREAS</text>

    <g font-family="Source Sans 3, sans-serif" font-size="9.5" fill="#46586a">
        <text x="24" y="92">Content quality</text>
        <text x="24" y="128">Policy</text>
        <text x="24" y="164">Trust and E-E-A-T</text>
        <text x="24" y="200">SEO and indexing</text>
        <text x="24" y="236">Technical</text>
        <text x="24" y="272">Traffic quality</text>
    </g>

    <g>
        <rect x="24" y="98" width="118" height="6" rx="1" fill="#e2e0da"/>
        <rect x="24" y="98" width="74" height="6" rx="1" fill="#1f6f4a"/>
        <rect x="24" y="134" width="118" height="6" rx="1" fill="#e2e0da"/>
        <rect x="24" y="134" width="49" height="6" rx="1" fill="#9a6512"/>
        <rect x="24" y="170" width="118" height="6" rx="1" fill="#e2e0da"/>
        <rect x="24" y="170" width="92" height="6" rx="1" fill="#1f6f4a"/>
        <rect x="24" y="206" width="118" height="6" rx="1" fill="#e2e0da"/>
        <rect x="24" y="206" width="81" height="6" rx="1" fill="#1f6f4a"/>
        <rect x="24" y="242" width="118" height="6" rx="1" fill="#e2e0da"/>
        <rect x="24" y="242" width="30" height="6" rx="1" fill="#a53a34"/>
        <rect x="24" y="278" width="118" height="6" rx="1" fill="#e2e0da"/>
        <rect x="24" y="278" width="63" height="6" rx="1" fill="#9a6512"/>
    </g>

    <line x1="24" y1="300" x2="142" y2="300" stroke="#e2e0da"/>
    <text x="24" y="322" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" letter-spacing="1.4" fill="#8a939f">VERDICT</text>
    <rect x="24" y="332" width="118" height="26" rx="2" fill="#f7eeda" stroke="#e7d5a9"/>
    <text x="83" y="349" font-family="Source Sans 3, sans-serif" font-size="9.5" font-weight="700" fill="#9a6512" text-anchor="middle">WAIT AND IMPROVE</text>

    <text x="24" y="386" font-family="IBM Plex Mono, monospace" font-size="8.5" fill="#8a939f">14 pages crawled</text>
    <text x="24" y="400" font-family="IBM Plex Mono, monospace" font-size="8.5" fill="#8a939f">28 findings</text>

    <text x="180" y="78" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" letter-spacing="1.4" fill="#8a939f">APPROVAL PROBABILITY</text>
    <text x="180" y="132" font-family="Tenor Sans, sans-serif" font-size="62" fill="#12395a">64<tspan font-size="30" fill="#5b7a94">%</tspan></text>

    <rect x="180" y="148" width="340" height="8" rx="1" fill="#e2e0da"/>
    <rect x="180" y="148" width="218" height="8" rx="1" fill="#12395a"/>
    <line x1="386" y1="143" x2="386" y2="161" stroke="#16202b" stroke-width="1.2"/>
    <text x="386" y="172" font-family="IBM Plex Mono, monospace" font-size="8" fill="#5b6775" text-anchor="middle">55</text>

    <line x1="180" y1="192" x2="520" y2="192" stroke="#e2e0da"/>
    <text x="180" y="212" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" letter-spacing="1.4" fill="#8a939f">FINDINGS</text>

    <g>
        <rect x="180" y="226" width="340" height="42" rx="2" fill="#fdf6f5" stroke="#e8c5c1"/>
        <rect x="180" y="226" width="3" height="42" fill="#a53a34"/>
        <text x="194" y="244" font-family="Source Sans 3, sans-serif" font-size="10" font-weight="600" fill="#16202b">Only 9 published posts</text>
        <text x="194" y="259" font-family="Source Sans 3, sans-serif" font-size="8.5" fill="#5b6775">Reviewers expect 25 or more substantial articles.</text>
    </g>

    <g>
        <rect x="180" y="276" width="340" height="42" rx="2" fill="#fdfaf2" stroke="#e7d5a9"/>
        <rect x="180" y="276" width="3" height="42" fill="#9a6512"/>
        <text x="194" y="294" font-family="Source Sans 3, sans-serif" font-size="10" font-weight="600" fill="#16202b">No author identity on articles</text>
        <text x="194" y="309" font-family="Source Sans 3, sans-serif" font-size="8.5" fill="#5b6775">Add a named author and a short biography page.</text>
    </g>

    <g>
        <rect x="180" y="326" width="340" height="42" rx="2" fill="#f4f9f6" stroke="#bfe0ce"/>
        <rect x="180" y="326" width="3" height="42" fill="#1f6f4a"/>
        <text x="194" y="344" font-family="Source Sans 3, sans-serif" font-size="10" font-weight="600" fill="#16202b">Privacy policy and contact page present</text>
        <text x="194" y="359" font-family="Source Sans 3, sans-serif" font-size="8.5" fill="#5b6775">Keep both visible in the main navigation.</text>
    </g>

    <rect x="180" y="378" width="128" height="22" rx="2" fill="#12395a"/>
    <text x="244" y="393" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" fill="#ffffff" text-anchor="middle">DOWNLOAD REPORT</text>
    <text x="320" y="393" font-family="IBM Plex Mono, monospace" font-size="8.5" fill="#8a939f">14 pages / 2.1 MB</text>
</svg>
'''

FILES['pipeline.blade.php'] = r'''
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
            <text x="36" y="147">/blog/10 posts</text>
            <circle cx="188" cy="143" r="4" fill="#1f6f4a"/>

            <rect x="24" y="164" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="36" y="181">/robots.txt</text>
            <circle cx="188" cy="177" r="4" fill="#1f6f4a"/>

            <rect x="24" y="198" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="36" y="215">/sitemap.xml</text>
            <circle cx="188" cy="211" r="4" fill="#9a6512"/>

            <rect x="24" y="232" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="36" y="249">/ads.txt</text>
            <circle cx="188" cy="245" r="4" fill="#a53a34"/>
        </g>
    </g>

    <line x1="232" y1="145" x2="268" y2="145" stroke="#12395a" stroke-width="1.4" marker-end="url(#arrowHead)"/>

    <g>
        <rect x="276" y="20" width="220" height="250" rx="4" fill="#ffffff" stroke="#e2e0da"/>
        <text x="296" y="52" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" letter-spacing="1.4" fill="#8a939f">STEP TWO</text>
        <text x="296" y="78" font-family="Tenor Sans, sans-serif" font-size="19" fill="#12395a">Run the checks</text>

        <g font-family="Source Sans 3, sans-serif" font-size="10" fill="#46586a">
            <rect x="296" y="96" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="308" y="113">Policy rules</text>
            <text x="462" y="113" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="9" fill="#8a939f">64</text>

            <rect x="296" y="130" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="308" y="147">Trust signals</text>
            <text x="462" y="147" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="9" fill="#8a939f">28</text>

            <rect x="296" y="164" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="308" y="181">Indexing and speed</text>
            <text x="462" y="181" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="9" fill="#8a939f">19</text>

            <rect x="296" y="198" width="180" height="26" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
            <text x="308" y="215">Model review</text>
            <text x="462" y="215" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="9" fill="#8a939f">9</text>
        </g>

        <rect x="296" y="238" width="180" height="20" rx="2" fill="#e8eef3"/>
        <text x="386" y="252" font-family="IBM Plex Mono, monospace" font-size="9" fill="#12395a" text-anchor="middle">120 signals in total</text>
    </g>

    <line x1="504" y1="145" x2="540" y2="145" stroke="#12395a" stroke-width="1.4" marker-end="url(#arrowHead)"/>

    <g>
        <rect x="548" y="20" width="208" height="250" rx="4" fill="#0c2a43"/>
        <text x="568" y="52" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" letter-spacing="1.4" fill="#7fb2d9">STEP THREE</text>
        <text x="568" y="78" font-family="Tenor Sans, sans-serif" font-size="19" fill="#ffffff">Write the report</text>

        <rect x="568" y="96" width="168" height="58" rx="2" fill="#12395a" stroke="#31597d"/>
        <text x="582" y="118" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" letter-spacing="1" fill="#9fc3de">PROBABILITY</text>
        <text x="582" y="144" font-family="Tenor Sans, sans-serif" font-size="28" fill="#ffffff">64%</text>
        <text x="660" y="144" font-family="Source Sans 3, sans-serif" font-size="9" fill="#9fc3de">wait and improve</text>

        <rect x="568" y="164" width="168" height="44" rx="2" fill="#12395a" stroke="#31597d"/>
        <circle cx="582" cy="178" r="3" fill="#a53a34"/>
        <text x="592" y="181" font-family="Source Sans 3, sans-serif" font-size="9" fill="#e6eef5">3 blockers listed</text>
        <circle cx="582" cy="196" r="3" fill="#9a6512"/>
        <text x="592" y="199" font-family="Source Sans 3, sans-serif" font-size="9" fill="#e6eef5">9 warnings listed</text>

        <rect x="568" y="218" width="168" height="40" rx="2" fill="#f5f4f0"/>
        <text x="652" y="236" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" fill="#12395a" text-anchor="middle">ROADMAP</text>
        <text x="652" y="249" font-family="Source Sans 3, sans-serif" font-size="8.5" fill="#5b6775" text-anchor="middle">7 ordered steps</text>
    </g>

    <text x="380" y="292" font-family="Source Sans 3, sans-serif" font-size="10" fill="#8a939f" text-anchor="middle">Every number in the report traces back to one of these checks.</text>
</svg>
'''

FILES['report-sheet.blade.php'] = r'''
<svg viewBox="0 0 420 520" class="w-full h-auto" role="img" aria-label="Preview of the PDF readiness report">
    <rect x="20" y="10" width="380" height="500" rx="3" fill="#ffffff" stroke="#cfcbc2"/>
    <rect x="20" y="10" width="380" height="96" fill="#0c2a43"/>
    <rect x="20" y="106" width="380" height="4" fill="#0c2a43"/>
    <text x="44" y="40" font-family="Source Sans 3, sans-serif" font-size="8" font-weight="700" letter-spacing="1.8" fill="#9fc3de">AD NETWORK READINESS REPORT</text>
    <text x="44" y="68" font-family="Tenor Sans, sans-serif" font-size="21" fill="#ffffff">example.com</text>
    <text x="44" y="88" font-family="Source Sans 3, sans-serif" font-size="9" fill="#cfe0ee">Prepared 14 March 2025 &middot; Standard audit</text>

    <text x="44" y="142" font-family="Source Sans 3, sans-serif" font-size="7.5" font-weight="700" letter-spacing="1.3" fill="#8a939f">APPROVAL PROBABILITY</text>
    <text x="44" y="186" font-family="Tenor Sans, sans-serif" font-size="44" fill="#12395a">64<tspan font-size="20" fill="#5b7a94">%</tspan></text>
    <rect x="44" y="200" width="332" height="7" rx="1" fill="#e2e0da"/>
    <rect x="44" y="200" width="212" height="7" rx="1" fill="#12395a"/>
    <rect x="44" y="218" width="120" height="20" rx="2" fill="#f7eeda"/>
    <text x="104" y="232" font-family="Source Sans 3, sans-serif" font-size="8" font-weight="700" fill="#9a6512" text-anchor="middle">WAIT AND IMPROVE</text>

    <text x="44" y="272" font-family="Source Sans 3, sans-serif" font-size="7.5" font-weight="700" letter-spacing="1.3" fill="#8a939f">READINESS BY AREA</text>
    <g font-family="Source Sans 3, sans-serif" font-size="8.5" fill="#46586a">
        <text x="44" y="292">Content quality</text>
        <text x="376" y="292" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8" fill="#5b6775">62</text>
        <rect x="44" y="298" width="332" height="5" rx="1" fill="#e2e0da"/>
        <rect x="44" y="298" width="206" height="5" rx="1" fill="#9a6512"/>

        <text x="44" y="322">Policy and compliance</text>
        <text x="376" y="322" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8" fill="#5b6775">41</text>
        <rect x="44" y="328" width="332" height="5" rx="1" fill="#e2e0da"/>
        <rect x="44" y="328" width="136" height="5" rx="1" fill="#a53a34"/>

        <text x="44" y="352">Trust and E-E-A-T</text>
        <text x="376" y="352" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8" fill="#5b6775">78</text>
        <rect x="44" y="358" width="332" height="5" rx="1" fill="#e2e0da"/>
        <rect x="44" y="358" width="259" height="5" rx="1" fill="#1f6f4a"/>

        <text x="44" y="382">SEO and indexing</text>
        <text x="376" y="382" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8" fill="#5b6775">70</text>
        <rect x="44" y="388" width="332" height="5" rx="1" fill="#e2e0da"/>
        <rect x="44" y="388" width="232" height="5" rx="1" fill="#1f6f4a"/>
    </g>

    <text x="44" y="424" font-family="Source Sans 3, sans-serif" font-size="7.5" font-weight="700" letter-spacing="1.3" fill="#8a939f">BLOCKERS TO FIX FIRST</text>
    <g>
        <rect x="44" y="436" width="332" height="30" rx="2" fill="#fdf6f5" stroke="#e8c5c1"/>
        <rect x="44" y="436" width="3" height="30" fill="#a53a34"/>
        <text x="56" y="450" font-family="Source Sans 3, sans-serif" font-size="8.5" font-weight="600" fill="#16202b">Only 9 published posts</text>
        <text x="56" y="461" font-family="Source Sans 3, sans-serif" font-size="7.5" fill="#5b6775">Publish 16 more substantial articles.</text>
    </g>
    <g>
        <rect x="44" y="472" width="332" height="30" rx="2" fill="#fdf6f5" stroke="#e8c5c1"/>
        <rect x="44" y="472" width="3" height="30" fill="#a53a34"/>
        <text x="56" y="486" font-family="Source Sans 3, sans-serif" font-size="8.5" font-weight="600" fill="#16202b">ads.txt is missing</text>
        <text x="56" y="497" font-family="Source Sans 3, sans-serif" font-size="7.5" fill="#5b6775">Publish the file at the root of the domain.</text>
    </g>
</svg>
'''

FILES['writer-card.blade.php'] = r'''
<svg viewBox="0 0 520 380" class="w-full h-auto" role="img" aria-label="Article writer interface with title, outline and FAQ">
    <rect x="10" y="8" width="500" height="364" rx="4" fill="#ffffff" stroke="#31597d"/>
    <rect x="10" y="8" width="500" height="36" rx="4" fill="#12395a"/>
    <rect x="10" y="36" width="500" height="8" fill="#12395a"/>
    <text x="30" y="32" font-family="Source Sans 3, sans-serif" font-size="10" font-weight="700" letter-spacing="1" fill="#ffffff">ARTICLE WRITER</text>
    <rect x="400" y="16" width="90" height="20" rx="2" fill="#0c2a43"/>
    <text x="445" y="30" font-family="Source Sans 3, sans-serif" font-size="8.5" font-weight="700" fill="#9fc3de" text-anchor="middle">STANDARD</text>

    <text x="30" y="72" font-family="Source Sans 3, sans-serif" font-size="8" font-weight="700" letter-spacing="1.2" fill="#8a939f">TITLE</text>
    <rect x="30" y="80" width="460" height="30" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
    <text x="42" y="99" font-family="Source Sans 3, sans-serif" font-size="10.5" fill="#16202b">How to choose a hosting provider that will not slow your site down</text>

    <g font-family="Source Sans 3, sans-serif" font-size="8" fill="#8a939f">
        <text x="30" y="134" letter-spacing="1.2" font-weight="700">META DESCRIPTION</text>
    </g>
    <rect x="30" y="142" width="460" height="34" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
    <text x="42" y="158" font-family="Source Sans 3, sans-serif" font-size="9" fill="#46586a">Hosting decides how fast your pages load. Here is how response time, uptime and</text>
    <text x="42" y="170" font-family="Source Sans 3, sans-serif" font-size="9" fill="#46586a">support quality affect the traffic you get from the search engines.</text>

    <g font-family="Source Sans 3, sans-serif" font-size="8" fill="#8a939f">
        <text x="30" y="200" letter-spacing="1.2" font-weight="700">OUTLINE</text>
    </g>
    <g font-family="Source Sans 3, sans-serif" font-size="9" fill="#46586a">
        <rect x="30" y="208" width="215" height="24" rx="2" fill="#e8eef3"/>
        <text x="42" y="224">1. What page speed really costs you</text>
        <rect x="30" y="238" width="215" height="24" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
        <text x="42" y="254">2. Shared, VPS or dedicated</text>
        <rect x="30" y="268" width="215" height="24" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
        <text x="42" y="284">3. What to measure after the move</text>
        <rect x="30" y="298" width="215" height="24" rx="2" fill="#f5f4f0" stroke="#e2e0da"/>
        <text x="42" y="314">4. Frequently asked questions</text>
    </g>

    <g font-family="Source Sans 3, sans-serif" font-size="8" fill="#8a939f">
        <text x="265" y="200" letter-spacing="1.2" font-weight="700">WORD COUNT</text>
    </g>
    <text x="265" y="226" font-family="Tenor Sans, sans-serif" font-size="26" fill="#12395a">2,438</text>
    <g font-family="Source Sans 3, sans-serif" font-size="8" fill="#8a939f">
        <text x="265" y="248" letter-spacing="1.2" font-weight="700">EXPORT</text>
    </g>
    <g font-family="Source Sans 3, sans-serif" font-size="8" font-weight="600" fill="#12395a">
        <rect x="265" y="256" width="62" height="20" rx="2" fill="#e8eef3"/>
        <text x="296" y="269" text-anchor="middle">WordPress</text>
        <rect x="333" y="256" width="52" height="20" rx="2" fill="#e8eef3"/>
        <text x="359" y="269" text-anchor="middle">Blogger</text>
        <rect x="391" y="256" width="46" height="20" rx="2" fill="#e8eef3"/>
        <text x="414" y="269" text-anchor="middle">Wix</text>
        <rect x="443" y="256" width="47" height="20" rx="2" fill="#e8eef3"/>
        <text x="466" y="269" text-anchor="middle">HTML</text>
    </g>
    <g font-family="Source Sans 3, sans-serif" font-size="8" fill="#8a939f">
        <text x="265" y="300" letter-spacing="1.2" font-weight="700">LINKS</text>
    </g>
    <g font-family="Source Sans 3, sans-serif" font-size="8.5" fill="#46586a">
        <circle cx="270" cy="316" r="3" fill="#1f6f4a"/>
        <text x="280" y="319">6 internal links</text>
        <circle cx="270" cy="332" r="3" fill="#12395a"/>
        <text x="280" y="335">4 external references</text>
    </g>
</svg>
'''

FILES['comparison-sheet.blade.php'] = r'''
<svg viewBox="0 0 460 340" class="w-full h-auto" role="img" aria-label="Comparison of two audit runs">
    <rect x="10" y="10" width="200" height="310" rx="4" fill="#ffffff" stroke="#e2e0da"/>
    <rect x="250" y="10" width="200" height="310" rx="4" fill="#ffffff" stroke="#c4d4e0"/>

    <text x="30" y="40" font-family="Source Sans 3, sans-serif" font-size="8" font-weight="700" letter-spacing="1.4" fill="#8a939f">BEFORE</text>
    <text x="270" y="40" font-family="Source Sans 3, sans-serif" font-size="8" font-weight="700" letter-spacing="1.4" fill="#12395a">AFTER 12 FIXES</text>

    <text x="30" y="88" font-family="Tenor Sans, sans-serif" font-size="42" fill="#8a939f">41<tspan font-size="18" fill="#8a939f">%</tspan></text>
    <text x="270" y="88" font-family="Tenor Sans, sans-serif" font-size="42" fill="#12395a">64<tspan font-size="18" fill="#5b7a94">%</tspan></text>
    <text x="30" y="106" font-family="Source Sans 3, sans-serif" font-size="8.5" fill="#8a939f">high risk of rejection</text>
    <text x="270" y="106" font-family="Source Sans 3, sans-serif" font-size="8.5" fill="#1f6f4a">wait and improve</text>

    <g font-family="Source Sans 3, sans-serif" font-size="9" fill="#46586a">
        <text x="30" y="140">Content quality</text>
        <text x="190" y="140" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8.5">34</text>
        <text x="270" y="140">Content quality</text>
        <text x="430" y="140" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8.5" fill="#1f6f4a">62</text>

        <text x="30" y="172">Policy</text>
        <text x="190" y="172" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8.5">28</text>
        <text x="270" y="172">Policy</text>
        <text x="430" y="172" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8.5" fill="#1f6f4a">41</text>

        <text x="30" y="204">Trust</text>
        <text x="190" y="204" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8.5">51</text>
        <text x="270" y="204">Trust</text>
        <text x="430" y="204" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8.5" fill="#1f6f4a">78</text>

        <text x="30" y="236">SEO</text>
        <text x="190" y="236" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8.5">44</text>
        <text x="270" y="236">SEO</text>
        <text x="430" y="236" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8.5" fill="#1f6f4a">70</text>
    </g>

    <g>
        <rect x="30" y="254" width="160" height="4" rx="1" fill="#e2e0da"/>
        <rect x="30" y="254" width="55" height="4" rx="1" fill="#a53a34"/>
        <rect x="270" y="254" width="160" height="4" rx="1" fill="#e2e0da"/>
        <rect x="270" y="254" width="99" height="4" rx="1" fill="#1f6f4a"/>

        <rect x="30" y="272" width="160" height="4" rx="1" fill="#e2e0da"/>
        <rect x="30" y="272" width="45" height="4" rx="1" fill="#a53a34"/>
        <rect x="270" y="272" width="160" height="4" rx="1" fill="#e2e0da"/>
        <rect x="270" y="272" width="66" height="4" rx="1" fill="#9a6512"/>

        <rect x="30" y="290" width="160" height="4" rx="1" fill="#e2e0da"/>
        <rect x="30" y="290" width="82" height="4" rx="1" fill="#9a6512"/>
        <rect x="270" y="290" width="160" height="4" rx="1" fill="#e2e0da"/>
        <rect x="270" y="290" width="125" height="4" rx="1" fill="#1f6f4a"/>
    </g>

    <text x="230" y="170" font-family="Tenor Sans, sans-serif" font-size="22" fill="#12395a" text-anchor="middle">+23</text>
    <text x="230" y="190" font-family="Source Sans 3, sans-serif" font-size="8" fill="#8a939f" text-anchor="middle">points</text>
</svg>
'''

FILES['contact-rail.blade.php'] = r'''
<svg viewBox="0 0 420 300" class="w-full h-auto" role="img" aria-label="Support channels">
    <g>
        <rect x="10" y="14" width="400" height="76" rx="4" fill="#ffffff" stroke="#e2e0da"/>
        <circle cx="52" cy="52" r="20" fill="#e8eef3"/>
        <path d="M42 52l7 7 13-14" fill="none" stroke="#12395a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <text x="88" y="46" font-family="Source Sans 3, sans-serif" font-size="12" font-weight="700" fill="#16202b">One business day reply</text>
        <text x="88" y="66" font-family="Source Sans 3, sans-serif" font-size="10" fill="#5b6775">Every message lands in the control panel and is answered by a person.</text>
    </g>

    <g>
        <rect x="10" y="104" width="400" height="76" rx="4" fill="#ffffff" stroke="#e2e0da"/>
        <circle cx="52" cy="142" r="20" fill="#e8eef3"/>
        <path d="M42 136h20v12H42z" fill="none" stroke="#12395a" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M42 136l10 7 10-7" fill="none" stroke="#12395a" stroke-width="1.8" stroke-linejoin="round"/>
        <text x="88" y="136" font-family="Source Sans 3, sans-serif" font-size="12" font-weight="700" fill="#16202b">Signed in users can chat</text>
        <text x="88" y="156" font-family="Source Sans 3, sans-serif" font-size="10" fill="#5b6775">The support page keeps the whole thread in one place, with your account attached.</text>
    </g>

    <g>
        <rect x="10" y="194" width="400" height="76" rx="4" fill="#ffffff" stroke="#e2e0da"/>
        <circle cx="52" cy="232" r="20" fill="#e8eef3"/>
        <path d="M44 232h16M52 224v16" stroke="#12395a" stroke-width="1.8" stroke-linecap="round"/>
        <text x="88" y="226" font-family="Source Sans 3, sans-serif" font-size="12" font-weight="700" fill="#16202b">Technical reports welcome</text>
        <text x="88" y="246" font-family="Source Sans 3, sans-serif" font-size="10" fill="#5b6775">If a check is wrong, tell us which site and we will look at the evidence.</text>
    </g>
</svg>
'''

FILES['referral-card.blade.php'] = r'''
<svg viewBox="0 0 460 320" class="w-full h-auto" role="img" aria-label="Referral dashboard preview">
    <rect x="10" y="10" width="440" height="300" rx="4" fill="#ffffff" stroke="#e2e0da"/>
    <rect x="10" y="10" width="440" height="46" rx="4" fill="#0c2a43"/>
    <rect x="10" y="46" width="440" height="10" fill="#0c2a43"/>
    <text x="30" y="38" font-family="Source Sans 3, sans-serif" font-size="11" font-weight="700" letter-spacing="1" fill="#ffffff">YOUR REFERRAL LINK</text>

    <rect x="30" y="76" width="400" height="40" rx="3" fill="#f5f4f0" stroke="#e2e0da"/>
    <text x="46" y="101" font-family="IBM Plex Mono, monospace" font-size="12" fill="#16202b">adready.app/ref/7KQ2M4XP</text>
    <rect x="356" y="84" width="62" height="24" rx="2" fill="#12395a"/>
    <text x="387" y="100" font-family="Source Sans 3, sans-serif" font-size="9" font-weight="700" fill="#ffffff" text-anchor="middle">COPY</text>

    <g font-family="Source Sans 3, sans-serif">
        <rect x="30" y="136" width="124" height="70" rx="3" fill="#f5f4f0"/>
        <text x="44" y="158" font-size="7.5" font-weight="700" letter-spacing="1.2" fill="#8a939f">CLICKS</text>
        <text x="44" y="190" font-family="Tenor Sans, sans-serif" font-size="24" fill="#12395a">248</text>

        <rect x="168" y="136" width="124" height="70" rx="3" fill="#f5f4f0"/>
        <text x="182" y="158" font-size="7.5" font-weight="700" letter-spacing="1.2" fill="#8a939f">SIGNUPS</text>
        <text x="182" y="190" font-family="Tenor Sans, sans-serif" font-size="24" fill="#12395a">31</text>

        <rect x="306" y="136" width="124" height="70" rx="3" fill="#f5f4f0"/>
        <text x="320" y="158" font-size="7.5" font-weight="700" letter-spacing="1.2" fill="#8a939f">EARNED</text>
        <text x="320" y="190" font-family="Tenor Sans, sans-serif" font-size="24" fill="#12395a">$186</text>
    </g>

    <text x="30" y="240" font-family="Source Sans 3, sans-serif" font-size="7.5" font-weight="700" letter-spacing="1.2" fill="#8a939f">RECENT ACTIVITY</text>
    <g font-family="Source Sans 3, sans-serif" font-size="9.5" fill="#46586a">
        <circle cx="34" cy="260" r="3" fill="#1f6f4a"/>
        <text x="46" y="263">Rafi joined with your link</text>
        <text x="430" y="263" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8.5" fill="#5b6775">+$4.90</text>

        <circle cx="34" cy="282" r="3" fill="#12395a"/>
        <text x="46" y="285">Nusrat upgraded to Standard</text>
        <text x="430" y="285" text-anchor="end" font-family="IBM Plex Mono, monospace" font-size="8.5" fill="#5b6775">+$7.20</text>
    </g>
</svg>
'''

FILES['network-map.blade.php'] = r'''
<svg viewBox="0 0 760 260" class="w-full h-auto" role="img" aria-label="One site scored against five ad networks">
    <rect x="20" y="86" width="150" height="88" rx="4" fill="#0c2a43"/>
    <text x="95" y="122" font-family="Tenor Sans, sans-serif" font-size="15" fill="#ffffff" text-anchor="middle">your site</text>
    <text x="95" y="142" font-family="Source Sans 3, sans-serif" font-size="9" fill="#9fc3de" text-anchor="middle">one crawl</text>
    <text x="95" y="158" font-family="IBM Plex Mono, monospace" font-size="8" fill="#7fb2d9" text-anchor="middle">14 pages</text>

    <g stroke="#c4d4e0" stroke-width="1.2" fill="none">
        <path d="M170 130 C 230 130, 240 40, 300 40"/>
        <path d="M170 130 C 230 130, 240 90, 300 90"/>
        <path d="M170 130 C 240 130, 250 130, 300 130"/>
        <path d="M170 130 C 230 130, 240 170, 300 170"/>
        <path d="M170 130 C 230 130, 240 220, 300 220"/>
    </g>

    <g font-family="Source Sans 3, sans-serif">
        <rect x="300" y="18" width="170" height="44" rx="3" fill="#ffffff" stroke="#e2e0da"/>
        <text x="314" y="38" font-size="10.5" font-weight="700" fill="#16202b">Google AdSense</text>
        <text x="314" y="53" font-size="8.5" fill="#5b6775">64 percent ready</text>
        <rect x="440" y="34" width="20" height="6" rx="1" fill="#e2e0da"/>
        <rect x="440" y="34" width="13" height="6" rx="1" fill="#9a6512"/>

        <rect x="300" y="68" width="170" height="44" rx="3" fill="#ffffff" stroke="#e2e0da"/>
        <text x="314" y="88" font-size="10.5" font-weight="700" fill="#16202b">Google Ad Manager</text>
        <text x="314" y="103" font-size="8.5" fill="#5b6775">41 percent ready</text>
        <rect x="440" y="84" width="20" height="6" rx="1" fill="#e2e0da"/>
        <rect x="440" y="84" width="8" height="6" rx="1" fill="#a53a34"/>

        <rect x="300" y="108" width="170" height="44" rx="3" fill="#ffffff" stroke="#e2e0da"/>
        <text x="314" y="128" font-size="10.5" font-weight="700" fill="#16202b">Ezoic</text>
        <text x="314" y="143" font-size="8.5" fill="#5b6775">78 percent ready</text>
        <rect x="440" y="124" width="20" height="6" rx="1" fill="#e2e0da"/>
        <rect x="440" y="124" width="16" height="6" rx="1" fill="#1f6f4a"/>

        <rect x="300" y="148" width="170" height="44" rx="3" fill="#ffffff" stroke="#e2e0da"/>
        <text x="314" y="168" font-size="10.5" font-weight="700" fill="#16202b">Mediavine</text>
        <text x="314" y="183" font-size="8.5" fill="#5b6775">28 percent ready</text>
        <rect x="440" y="164" width="20" height="6" rx="1" fill="#e2e0da"/>
        <rect x="440" y="164" width="6" height="6" rx="1" fill="#a53a34"/>

        <rect x="300" y="198" width="170" height="44" rx="3" fill="#ffffff" stroke="#e2e0da"/>
        <text x="314" y="218" font-size="10.5" font-weight="700" fill="#16202b">Raptive</text>
        <text x="314" y="233" font-size="8.5" fill="#5b6775">52 percent ready</text>
        <rect x="440" y="214" width="20" height="6" rx="1" fill="#e2e0da"/>
        <rect x="440" y="214" width="10" height="6" rx="1" fill="#9a6512"/>
    </g>

    <rect x="490" y="18" width="250" height="224" rx="4" fill="#f5f4f0" stroke="#e2e0da"/>
    <text x="510" y="44" font-family="Source Sans 3, sans-serif" font-size="8" font-weight="700" letter-spacing="1.3" fill="#8a939f">WHAT CHANGES</text>
    <g font-family="Source Sans 3, sans-serif" font-size="9.5" fill="#46586a">
        <text x="510" y="72">25 real articles lifts</text>
        <text x="510" y="88">AdSense and Ezoic at once.</text>
        <text x="510" y="114">An author page and an</text>
        <text x="510" y="130">about page lift Mediavine</text>
        <text x="510" y="146">the most.</text>
        <text x="510" y="172">Publishing consistently</text>
        <text x="510" y="188">is what moves traffic,</text>
        <text x="510" y="204">not a clever trick.</text>
    </g>
</svg>
'''

for name, content in FILES.items():
    with open(os.path.join(ROOT, name), 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} visual partials")
