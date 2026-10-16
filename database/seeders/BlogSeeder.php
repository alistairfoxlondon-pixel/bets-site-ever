<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('role', User::ROLE_ADMIN)->first();

        $posts = [
            [
                'title' => 'Assess content depth without chasing a post count',
                'slug' => 'the-twenty-five-post-rule',
                'excerpt' => 'There is no universal article-count target that guarantees a network decision. Focus on useful pages, clear purpose and evidence readers can verify.',
                'meta_title' => 'Assess content depth without chasing a post count',
                'meta_description' => 'A practical way to review content usefulness, crawl coverage and page purpose without treating arbitrary word or post counts as requirements.',
                'tags' => ['publishing', 'content'],
                'body' => <<<'HTML'
<p>Publishers often ask how many articles a site needs before applying to an advertising network. A public-page audit cannot answer that question with a reliable number, and a fixed post count is not a substitute for current program requirements. The more useful question is whether the pages a reader can reach are clear, helpful and appropriate for their purpose.</p>
<h2>Start with the reader's task</h2>
<p>Choose a representative page and write down what a reader came to do. Does the page answer that question directly? Are important caveats, examples or sources missing? Is the next step clear? These checks are more useful than adding paragraphs to meet an arbitrary length.</p>
<h2>Review the whole page type</h2>
<p>Short pages are not automatically low quality. A contact page, glossary entry or tool can be concise and still do its job. Longer pages can also be repetitive or unhelpful. Review each page in context, including whether it repeats another page, serves a distinct purpose and is reachable through useful navigation.</p>
<h2>Understand what a crawl can miss</h2>
<p>A crawler samples pages based on the URLs it can discover and the limits of the audit. It may miss pages behind scripts, unusual navigation, access controls or non-standard sitemaps. Treat a detected page count as a snapshot, not a complete inventory.</p>
<h2>A practical review</h2>
<ol>
<li>List the main page types on the site.</li>
<li>Inspect representative pages for accuracy, usefulness, originality and clear sourcing.</li>
<li>Check that important pages are reachable and that temporary, duplicate or empty pages are handled intentionally.</li>
<li>Use analytics and search-console data separately to understand how people find and use the site.</li>
<li>Confirm current requirements directly with each network before applying.</li>
</ol>
<p>Publishing more can make sense when you have useful, well-supported topics to cover. It is not a guarantee of approval, and length alone does not establish quality.</p>
HTML,
            ],
            [
                'title' => 'Review policy signals without assuming a violation',
                'slug' => 'policy-rejections-explained',
                'excerpt' => 'Automated site checks can flag patterns for review, but only the relevant network can interpret its current rules and account-specific decision.',
                'meta_title' => 'Review advertising policy signals carefully',
                'meta_description' => 'How to investigate policy-related website findings, validate evidence and check current requirements without treating a heuristic as a verdict.',
                'tags' => ['policy', 'publishing'],
                'body' => <<<'HTML'
<p>A policy-related warning is a reason to inspect a page, not proof that a network will reject it. Automated tools can recognise visible patterns such as missing disclosures, unusual redirects or download links, but they may lack context and can produce false positives.</p>
<h2>Check the source and the context</h2>
<p>Open the exact URL named in a finding. Confirm that the page is live, that the reported element is present and that it is not a browser, cache or crawler artefact. A file extension, topic keyword or script reference is not enough to establish a policy violation.</p>
<h2>Use current official guidance</h2>
<p>Policies and program terms can change, and requirements can differ between products and account types. Read the current material from the network you intend to use. If a decision depends on your account, contact the network or its authorised representative rather than relying on a generic checklist.</p>
<h2>Make commercial relationships clear</h2>
<p>When a page contains affiliate links, sponsorships or another commercial relationship, review the disclosure rules that apply to your audience and jurisdiction. Make disclosures understandable and place them where readers can see them. Do not assume that a site-wide footer alone covers every context.</p>
<h2>Keep a record of changes</h2>
<p>Record the URL, what you observed, the source you checked and the change you made. This helps distinguish a genuine fix from a score fluctuation caused by crawl coverage, content changes or temporary server responses.</p>
<p>A readiness report can help organise that review. It cannot provide a binding interpretation of policy or guarantee an application outcome.</p>
HTML,
            ],
            [
                'title' => 'Trust signals: what a public crawl can and cannot tell you',
                'slug' => 'eeat-is-a-habit',
                'excerpt' => 'Publisher identity, authorship and transparent sourcing help readers judge a site. A public crawl can detect some signals, but it cannot verify expertise or experience.',
                'meta_title' => 'Build clear trust signals for a publisher site',
                'meta_description' => 'Practical ways to make authorship, sourcing, corrections and commercial disclosures clear without inventing experience or treating trust as a score.',
                'tags' => ['trust', 'editorial'],
                'body' => <<<'HTML'
<p>Readers need to know who is responsible for a page, how its claims are supported and whether a commercial relationship affects the advice. Clear information helps readers make that assessment. It is not a shortcut to a ranking or an advertising decision.</p>
<h2>Make authorship accurate</h2>
<p>Name the people who actually wrote or reviewed a piece where that information helps the reader. A short biography should describe real, relevant experience and link to sources that support important credentials. Do not invent expertise, testing or personal use to make a page sound more authoritative.</p>
<h2>Show how the publication works</h2>
<p>An about page can explain the publication's scope and who runs it. An editorial or corrections page can describe how claims are checked and how errors are handled. Keep these pages specific to the organisation rather than copying a template that describes practices you do not follow.</p>
<h2>Support important claims</h2>
<p>Use reliable sources for factual claims, especially where information changes or could affect a reader's health, finances or safety. Link to the original source where appropriate, distinguish fact from opinion and date material when its timeliness matters.</p>
<h2>Be transparent about relationships</h2>
<p>Disclose affiliate links, sponsorships or other commercial interests in a way that is clear to readers. Make sure the disclosure accurately describes the relationship and appears in the context where it matters.</p>
<h2>Know what automation cannot verify</h2>
<p>A crawler may find an author name, a contact link or a privacy page. It cannot establish that a person has the stated expertise, that an email address is monitored, or that a policy is legally adequate. Review those details yourself and check any applicable current rules.</p>
HTML,
            ],
        ];

        foreach ($posts as $index => $post) {
            BlogPost::updateOrCreate(
                ['slug' => $post['slug']],
                array_merge($post, [
                    'status' => 'published',
                    'published_at' => now()->subDays(count($posts) - $index),
                    'author_id' => $author?->id,
                ])
            );
        }
    }
}
