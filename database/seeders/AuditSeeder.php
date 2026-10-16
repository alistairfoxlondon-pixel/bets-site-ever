<?php

namespace Database\Seeders;

use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'demo@adready.test')->first();

        if (! $user) {
            $this->command?->warn('demo user missing, run the user seeder first');

            return;
        }

        $runs = [
            [
                'domain' => 'brightsidegear.com',
                'probability' => 41,
                'verdict' => 'high_risk',
                'score' => 41,
                'days_ago' => 26,
                'category_scores' => [
                    'content_quality' => 34,
                    'policy' => 58,
                    'trust' => 27,
                    'seo' => 46,
                    'technical' => 71,
                    'traffic' => 22,
                ],
                'network_readiness' => [
                    'adsense' => ['ready' => false, 'score' => 34, 'notes' => ['Nine published posts, most of them shorter than six hundred words.', 'No author byline on a single article.']],
                    'adx' => ['ready' => false, 'score' => 12, 'notes' => ['Direct sold inventory does not exist yet.']],
                    'ezoic' => ['ready' => false, 'score' => 30, 'notes' => ['Traffic is well below the five hundred visitor floor.']],
                    'mediavine' => ['ready' => false, 'score' => 8, 'notes' => ['Session volume and original depth are both short of the bar.']],
                    'raptive' => ['ready' => false, 'score' => 22, 'notes' => ['Twenty five thousand monthly page views are missing.']],
                ],
                'analysis' => [
                    'website_quality' => "The site loads quickly and the layout is calm, but the editorial bar is low. A visitor lands on the home page, sees a category list and a thin teaser for each post, and has no reason to come back. There is no author page, no about page that names a person, and no sign that anybody is accountable for the content.",
                    'content_quality' => "Nine posts carry the whole site. The best of them run a little over six hundred words and lean on material that appears elsewhere. There is no original reporting, no first hand photographs, no product testing and no point of view. For a review site that sells outdoor gear this is the single biggest problem.",
                    'traffic_quality' => "Traffic comes almost entirely from a handful of social posts and a small newsletter. Sessions are short, the bounce rate is high and there is no list building, no email capture and no returning readership. Reviewers see a site that has not yet earned an audience.",
                    'policy_risks' => "Nothing in the policy set is openly broken, but the mix of affiliate links, incentivised posts and thin round ups is close to the line. Several articles are little more than rewritten product descriptions, which is the pattern most often associated with a low value outcome.",
                    'trust_eeat' => "There is no author identity anywhere on the site, no disclosure page explaining how the site makes money, and no record of who owns the domain. The about page is three sentences long and mentions no experience. Trust signals are close to zero.",
                    'seo_indexing' => "Every post is indexed and the titles are reasonable, but the internal linking is thin, the images have no alt text, and there is no structured data for products or reviews. A large part of the archive sits on page two because most posts are short and answer nothing.",
                ],
                'strengths' => [
                    'The site is fast on a phone and the navigation is clear.',
                    'Every article is reachable in two clicks from the home page.',
                    'The domain has a clean history with no penalty on record.',
                ],
                'risks' => [
                    'Thin round ups that read like rewritten product descriptions.',
                    'No author identity and no editorial accountability.',
                    'Almost no returning traffic and no email list.',
                ],
                'critical_fixes' => [
                    'Publish a named author with a real photo and a short biography on every article.',
                    'Write an about page, a contact page and a clear affiliate disclosure page.',
                    'Replace the thin round ups with original testing, photographs and measurements.',
                    'Build an email list and send a weekly digest so readers come back.',
                ],
                'roadmap' => [
                    ['title' => 'Add people to the site', 'description' => 'Create one author page with a photograph, a short career history and a link from every article. Reviewers look for a person, not a logo.'],
                    ['title' => 'Replace the weakest five posts', 'description' => 'Take the five shortest round ups and rebuild them around first hand testing, your own photographs and a clear opinion.'],
                    ['title' => 'Write the trust pages', 'description' => 'About, contact, privacy, terms and an affiliate disclosure page that explains how the site earns money and who funds it.'],
                    ['title' => 'Publish on a schedule', 'description' => 'Two substantial posts a week for twelve weeks. Consistency is a stronger signal than a single long article.'],
                    ['title' => 'Start an email list', 'description' => 'Add a signup box to every post and send a weekly digest. Returning readers are the clearest traffic signal you can build.'],
                    ['title' => 'Re-run the audit', 'description' => 'Run the audit again in eight weeks and compare the score with this one. The roadmap is designed to be walked in order.'],
                ],
                'summary' => 'A clean but thin site that a reviewer would reject today. The design is not the problem. The missing people, the missing depth and the missing audience are.',
            ],
            [
                'domain' => 'brightsidegear.com',
                'probability' => 68,
                'verdict' => 'wait_and_improve',
                'score' => 68,
                'days_ago' => 2,
                'category_scores' => [
                    'content_quality' => 66,
                    'policy' => 84,
                    'trust' => 61,
                    'seo' => 71,
                    'technical' => 78,
                    'traffic' => 48,
                ],
                'network_readiness' => [
                    'adsense' => ['ready' => false, 'score' => 68, 'notes' => ['Twenty one posts now, four short of the usual expectation.', 'Author pages exist but the biography is thin.', 'Traffic is still the weakest area.']],
                    'adx' => ['ready' => false, 'score' => 20, 'notes' => ['Direct sold inventory still does not exist.']],
                    'ezoic' => ['ready' => false, 'score' => 55, 'notes' => ['Visitor numbers are close to the floor and rising.', 'Original depth is now acceptable on most posts.']],
                    'mediavine' => ['ready' => false, 'score' => 31, 'notes' => ['Session volume remains far below the requirement.']],
                    'raptive' => ['ready' => false, 'score' => 44, 'notes' => ['Page views have doubled but are not there yet.']],
                ],
                'analysis' => [
                    'website_quality' => "The site has turned into a real publication. Twenty one posts sit behind a clean category structure, and the home page now leads with the most useful reading rather than a wall of links. The pages are quick and the reading experience holds up on a phone.",
                    'content_quality' => "Depth is much better. Six posts now carry original photographs, measurements and a clear recommendation, and the rest are far longer than before. The remaining weakness is that the newest posts lean on manufacturer copy in the opening paragraph.",
                    'traffic_quality' => "Traffic is climbing but still shallow. The email list has two thousand subscribers, which helps, but a large share of sessions still arrives from social platforms and leaves after one page. Average time on site has improved from forty seconds to just over two minutes.",
                    'policy_risks' => "The policy picture is clean. The disclosure page is linked from the footer and from the first paragraph of every affiliate post, and no content sits near a restricted category. The only outstanding point is that some older posts still carry referral links without a disclosure line.",
                    'trust_eeat' => "Trust has improved the most. Every article names an author, the about page explains the editorial process, and the site now publishes a corrections policy. The author biographies are still short, and there is no evidence of first hand professional experience, which is the one thing reviewers still ask about.",
                    'seo_indexing' => "Titles, meta descriptions and internal linking have all been rewritten. The archive has gone from page two dominance to mostly page one, and structured data for reviews and products is live. A handful of thin legacy posts still drag the average quality score down.",
                ],
                'strengths' => [
                    'Twenty one posts with real original testing in the newest six.',
                    'Named authors on every article and a corrections policy.',
                    'Disclosure on every affiliate post, linked from the footer.',
                    'A two thousand subscriber email list that sends weekly.',
                ],
                'risks' => [
                    'Traffic still depends on social platforms for most sessions.',
                    'A handful of old posts still carry referral links without a disclosure.',
                    'Author biographies do not yet show professional experience.',
                ],
                'critical_fixes' => [
                    'Add four more substantial posts to clear the usual twenty five post expectation.',
                    'Add a disclosure line to the remaining legacy posts that carry referral links.',
                    'Rewrite the author biographies to name the professional experience behind the site.',
                    'Build two more traffic sources so the site is not dependent on one platform.',
                ],
                'roadmap' => [
                    ['title' => 'Publish four more posts', 'description' => 'Keep the current rhythm of two substantial posts a week. Twenty five posts is the number most reviewers expect to see.'],
                    ['title' => 'Fix the legacy disclosure gaps', 'description' => 'Add the disclosure sentence to the older posts that still carry referral links without one.'],
                    ['title' => 'Strengthen the author pages', 'description' => 'Name the professional experience behind each author and add a portrait and a link to a profile page.'],
                    ['title' => 'Add a second traffic source', 'description' => 'Either a weekly video round up or a partnership with two related newsletters. Do not rely on a single platform.'],
                    ['title' => 'Re-run the audit', 'description' => 'Run the audit again in four weeks. The score has moved twenty seven points since the first run, and the trend matters as much as the number.'],
                ],
                'summary' => 'A credible publication that is not quite ready. The content and trust work is paying off and the score has moved twenty seven points in three weeks. Traffic and volume are the two things still standing between this site and an approval.',
            ],
        ];

        Audit::query()->where('user_id', $user->id)->where('domain', 'brightsidegear.com')->each(function (Audit $existing) {
            $existing->findings()->delete();
            $existing->delete();
        });

        foreach ($runs as $run) {
            $audit = Audit::create([
                    'user_id' => $user->id,
                    'url' => 'https://'.$run['domain'],
                    'normalized_url' => 'https://'.$run['domain'],
                    'domain' => $run['domain'],
                    'mode' => 'standard',
                    'status' => Audit::STATUS_COMPLETED,
                    'score' => $run['score'],
                    'probability' => $run['probability'],
                    'verdict' => $run['verdict'],
                    'model' => 'anthropic/claude-sonnet-4.5',
                    'tokens_used' => 8450,
                    'cost' => 0.0342,
                    'duration_ms' => 96400,
                    'pages_crawled' => 14,
                    'findings_count' => 0,
                    'started_at' => now()->subDays($run['days_ago'])->subMinutes(3),
                    'completed_at' => now()->subDays($run['days_ago']),
                    'meta' => [
                        'credits_cost' => 3,
                        'category_scores' => $run['category_scores'],
                        'network_readiness' => $run['network_readiness'],
                        'analysis' => $run['analysis'],
                        'strengths' => $run['strengths'],
                        'risks' => $run['risks'],
                        'critical_fixes' => $run['critical_fixes'],
                        'roadmap' => $run['roadmap'],
                        'summary' => $run['summary'],
                    ],
                ]
            );

            $audit->findings()->delete();

            $findings = $this->findingsFor($run['days_ago'] < 10 ? 1 : 0);

            foreach ($findings as $index => $finding) {
                AuditFinding::create(array_merge($finding, [
                    'audit_id' => $audit->id,
                    'sort_order' => $index,
                ]));
            }

            $audit->update(['findings_count' => count($findings)]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findingsFor(int $generation): array
    {
        $before = [
            ['category' => 'content_quality', 'severity' => 'critical', 'title' => 'Only nine published posts', 'detail' => 'The archive holds nine posts. Reviewers consistently expect at least twenty five to thirty substantial articles before a site is worth monetising.', 'recommendation' => 'Publish two substantial posts a week for twelve weeks and keep every one of them above one thousand words.', 'evidence' => ['posts_published' => 9, 'expected_minimum' => 25], 'weight' => 16],
            ['category' => 'content_quality', 'severity' => 'critical', 'title' => 'Most posts read like rewritten product descriptions', 'detail' => 'Seven of the nine posts follow the same shape: a paragraph of manufacturer copy, a list of specifications and a link. There is no testing, no measurement and no opinion.', 'recommendation' => 'Rewrite the five shortest posts around first hand use, your own photographs and a recommendation you are willing to defend.', 'evidence' => ['thin_posts' => 7, 'total_posts' => 9], 'weight' => 14],
            ['category' => 'trust', 'severity' => 'critical', 'title' => 'No author identity anywhere on the site', 'detail' => 'No article names an author, there is no author page and the about page does not name a person. A reviewer cannot tell who is accountable for the content.', 'recommendation' => 'Create one author page with a photograph and a short biography, and sign every post.', 'evidence' => ['authors_found' => 0], 'weight' => 15],
            ['category' => 'trust', 'severity' => 'warning', 'title' => 'Trust pages are missing', 'detail' => 'The site has no contact page, no privacy page and no page explaining how it makes money.', 'recommendation' => 'Publish about, contact, privacy, terms and an affiliate disclosure page, and link all five from the footer.', 'evidence' => ['missing_pages' => ['contact', 'privacy', 'about', 'disclosure']], 'weight' => 9],
            ['category' => 'traffic', 'severity' => 'critical', 'title' => 'Almost no returning visitors', 'detail' => 'The median session lasts forty seconds and the email list is empty. Every visit starts from a social post.', 'recommendation' => 'Add an email signup to every article and send a weekly digest of the best writing.', 'evidence' => ['avg_session_seconds' => 40, 'email_subscribers' => 0], 'weight' => 13],
            ['category' => 'policy', 'severity' => 'warning', 'title' => 'Affiliate disclosure is inconsistent', 'detail' => 'Several posts carry referral links without a disclosure line, and there is no policy page describing the relationship with the merchant.', 'recommendation' => 'Add a disclosure sentence to the opening paragraph of every post that earns money, and publish a disclosure page.', 'evidence' => ['posts_without_disclosure' => 6], 'weight' => 8],
            ['category' => 'seo', 'severity' => 'warning', 'title' => 'Images have no alt text', 'detail' => 'Ninety one per cent of the images on the site carry no alt attribute, which hurts both accessibility and image search.', 'recommendation' => 'Write a short, descriptive alt text for every image and keep the file names readable.', 'evidence' => ['images_missing_alt' => 148, 'images_total' => 163], 'weight' => 6],
            ['category' => 'technical', 'severity' => 'pass', 'title' => 'The site is fast on a phone', 'detail' => 'Largest contentful paint lands in 1.1 seconds on a mid range Android over a slow connection, and there is no layout shift.', 'recommendation' => 'Keep the image pipeline as it is. Nothing here needs to change.', 'evidence' => ['lcp_seconds' => 1.1, 'cls' => 0.01], 'weight' => 0],
            ['category' => 'technical', 'severity' => 'info', 'title' => 'Structured data is missing', 'detail' => 'The site publishes no product, review or article structured data, so rich results are not available in search.', 'recommendation' => 'Add article and review markup to the post template once the content is ready to be reviewed.', 'evidence' => [], 'weight' => 3],
        ];

        $after = [
            ['category' => 'content_quality', 'severity' => 'pass', 'title' => 'Twenty one substantial posts are live', 'detail' => 'The archive now holds twenty one posts, most of them longer than fifteen hundred words, and the six newest carry original testing, measurements and first hand photographs.', 'recommendation' => 'Keep the current rhythm of two posts a week until you clear twenty five.', 'evidence' => ['posts_published' => 21, 'expected_minimum' => 25], 'weight' => 0],
            ['category' => 'content_quality', 'severity' => 'warning', 'title' => 'Some newer posts still open with manufacturer copy', 'detail' => 'Four of the newest posts begin with a paragraph lifted from the manufacturer page before the original testing begins.', 'recommendation' => 'Lead every post with your own conclusion. The product copy can live in the specification table.', 'evidence' => ['posts_opening_with_boilerplate' => 4], 'weight' => 6],
            ['category' => 'trust', 'severity' => 'pass', 'title' => 'Every article names an author', 'detail' => 'Each post carries a byline that links to an author page with a photograph, a short biography and a corrections policy link.', 'recommendation' => 'Strengthen the biographies with professional experience, which is the remaining gap.', 'evidence' => ['authors_found' => 3, 'posts_with_byline' => 21], 'weight' => 0],
            ['category' => 'trust', 'severity' => 'warning', 'title' => 'Author biographies are short', 'detail' => 'The three author pages describe general interests but do not name the professional experience behind the site.', 'recommendation' => 'Write two sentences about real field experience for each author.', 'evidence' => [], 'weight' => 5],
            ['category' => 'traffic', 'severity' => 'critical', 'title' => 'Traffic is still too shallow', 'detail' => 'The email list has two thousand subscribers, but seventy one per cent of sessions still arrive from one social platform and the average time on site is just over two minutes.', 'recommendation' => 'Add a second traffic source and keep the weekly digest going.', 'evidence' => ['email_subscribers' => 2000, 'avg_session_seconds' => 128, 'social_share' => 0.71], 'weight' => 12],
            ['category' => 'policy', 'severity' => 'warning', 'title' => 'Four legacy posts still carry referral links without a disclosure', 'detail' => 'The disclosure line was added to every new post, but four posts from the first year still link to merchants without it.', 'recommendation' => 'Add the disclosure sentence to those four posts and re-check the archive once a year.', 'evidence' => ['posts_without_disclosure' => 4], 'weight' => 7],
            ['category' => 'seo', 'severity' => 'pass', 'title' => 'Internal linking and structured data are in place', 'detail' => 'Every post links to three related posts, and review and product structured data validate on the template.', 'recommendation' => 'Keep the template as it is.', 'evidence' => ['structured_data_valid' => true, 'internal_links_per_post' => 3], 'weight' => 0],
            ['category' => 'technical', 'severity' => 'pass', 'title' => 'The site remains fast', 'detail' => 'Largest contentful paint is 1.0 seconds on a mid range Android and layout shift stays under 0.02.', 'recommendation' => 'No change needed.', 'evidence' => ['lcp_seconds' => 1.0, 'cls' => 0.02], 'weight' => 0],
        ];

        return $generation === 0 ? $before : $after;
    }
}
