<style>
    html { box-sizing: border-box; }
    *, *::before, *::after { box-sizing: inherit; }
    body { margin: 0; background: #f5f4f0; color: #16202b; font-family: 'Source Sans 3', 'Segoe UI', sans-serif; }
    img, svg { max-width: 100%; }
    svg[aria-hidden="true"] { width: 20px; height: 20px; flex: none; display: inline-block; vertical-align: middle; }
    a { color: inherit; }
    .wrap { width: min(1120px, calc(100% - 2.5rem)); margin-inline: auto; }
    .wrap-narrow { width: min(820px, calc(100% - 2.5rem)); margin-inline: auto; }
    .nav-shell { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 64px; }
    .site-header { background: #fff; border-bottom: 1px solid #e2e0da; position: sticky; top: 0; z-index: 40; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; padding: .62rem 1.05rem; border: 1px solid #cfcbc2; background: #fff; color: #16202b; text-decoration: none; font-weight: 600; font-size: .95rem; border-radius: 3px; }
    .btn-primary { background: #12395a; color: #fff; border-color: #12395a; }
    .btn-ghost { background: transparent; }
    .band { padding: 4rem 0; }
    .grid { display: grid; gap: 1.25rem; }
    .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .nav-drawer:not(.is-open), .nav-drawer[hidden] { display: none !important; }
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    .visual-frame svg { display: block; width: 100%; height: auto; }
    .logo-strip { display: flex; flex-wrap: wrap; align-items: center; gap: 1.25rem 2rem; }
    .logo-strip img { height: 26px; width: auto; max-width: 130px; }
    .app-shell { display: grid; grid-template-columns: 246px minmax(0, 1fr); min-height: 100vh; }
    .sidebar { background: #fff; border-right: 1px solid #e2e0da; padding: 1.25rem .9rem 2rem; }
    .card, .panel, .stat, .feature { background: #fff; border: 1px solid #e2e0da; border-radius: 4px; }
    @media (max-width: 900px) {
        .nav-links { display: none !important; }
        .app-shell { grid-template-columns: minmax(0, 1fr); }
        .sidebar:not(.is-open) { display: none !important; }
        .grid-2, .grid-3, .grid-4 { grid-template-columns: minmax(0, 1fr); }
    }
    @media (min-width: 901px) { .nav-toggle { display: none !important; } }
</style>
