<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('role', 'admin')->first();

        $posts = [
            [
                'title' => 'The twenty five post rule, and what actually counts as a post',
                'slug' => 'the-twenty-five-post-rule',
                'excerpt' => 'Reviewers talk about 25 to 30 articles, but nobody tells you what stops counting as one. Here is the version that survives a review.',
                'meta_title' => 'The twenty five post rule for AdSense, explained',
                'meta_description' => 'What counts as a published post for a Google AdSense review, why thin pages do not count, and how to build a library that passes.',
                'tags' => ['adsense', 'content'],
                'body' => <<<'HTML'
<p>Every publisher hears the same number before they apply. Twenty five posts, sometimes thirty. It comes up in almost every forum thread about a first rejection, and it is repeated often enough that people treat it like a rule from a manual. It is not. It is a pattern, and understanding the pattern is worth more than the number.</p>
<h2>Why the number exists at all</h2>
<p>A reviewer opening a new account has no history to work with. They cannot see your traffic, they cannot see how long you have been writing, and they will not spend an hour reading your site. What they can do is open a few pages and ask a simple question: does this look like a real publication that a reader would come back to, or does it look like a site built to collect a payout?</p>
<p>A site with nine posts and an obvious point of view can pass that test. A site with forty thin posts fails it. The number is a proxy for something real, which is volume, depth and the visible shape of a publishing habit. But the proxy breaks in both directions, and that is where publishers get hurt.</p>
<h2>What stops counting</h2>
<p>Three things remove a page from the effective count, and all three are common.</p>
<h3>Tag and category archives</h3>
<p>An archive page repeats the same excerpts you already have on the articles themselves. Google has indexed those articles. A reviewer who lands on an archive sees duplication, not volume. Keep archives out of the navigation and out of the internal links that your main pages use.</p>
<h3>Short announcements</h3>
<p>A post that says the site launched, or thanks a reader, or announces a redesign, has no information value. A handful of these is normal and harmless. Twenty of them is a pattern a reviewer will read as an empty site.</p>
<h3>Anything behind a wall</h3>
<p>If your best writing needs a login, it does not exist for a reviewer. Subscriber-only articles are fine on an established site with a real audience. On a first application they are invisible.</p>
<h2>What actually counts</h2>
<p>The reverse is worth stating clearly, because it is where publishers can build an advantage. A post counts when it answers a question somebody typed into a search engine, and it keeps answering it after the first paragraph.</p>
<p>That means a piece of 1,200 words that walks through a decision step by step, with the tradeoffs and the failure cases, is worth more than three 400 word summaries of the same topic. The second kind is the classic reason for rejection. It looks like volume from the outside and it is padding from the inside.</p>
<h2>What to do this month</h2>
<p>Open your own site and count the pages you would defend to a stranger. If that number is under fifteen, publishing is the bottleneck and nothing else matters until it changes. If it is above fifteen, the problem is probably depth rather than count, and the report will tell you which pages are thin.</p>
<p>Either way, the fix is the same: publish fewer, better pieces, and make sure every one of them has a named author, a clear purpose and something a reader would save.</p>
HTML,
            ],
            [
                'title' => 'Why publishers get rejected for policy, not for thin content',
                'slug' => 'policy-rejections-explained',
                'excerpt' => 'A rejection email never names the policy you broke. These are the four that account for most of them, and how to spot each one on your own site.',
                'meta_title' => 'Why sites get rejected for AdSense policy, not thin content',
                'meta_description' => 'The four policy failures behind most AdSense rejections, how to recognise them on your own site, and what a reviewer actually sees.',
                'tags' => ['policy', 'adsense'],
                'body' => <<<'HTML'
<p>When a site is rejected, the email is deliberately vague. It usually says the site does not meet the quality standards and invites you to apply again. Publishers read that as a judgement on their writing, and they spend the next month writing longer posts. That is often the wrong work.</p>
<p>Plenty of rejected sites have plenty of content. They failed on policy, and policy is visible long before a human reviewer opens a page.</p>
<h2>One: missing or hidden policy pages</h2>
<p>This is the most common reason and the easiest to fix. Reviewers look for a contact page, an about page and a privacy policy. Not a privacy policy buried under a menu labelled resources. A page that a visitor can reach in one click from the home page or the footer.</p>
<p>If your contact page is a mailto link, that is not always enough. A real page with a form or an address and a response time reads as a person running the site. If your privacy page is a 2019 template for a different company, fix that first. Reviewers notice the mismatch, and so do readers.</p>
<h2>Two: the health and finance cluster</h2>
<p>Google's policy is unusually detailed about content that claims to help people get rich quickly, about payday loans and about anything that reads like a get rich scheme. It also cares about misleading claims, exaggerated earnings screenshots and pages that exist only to send people to a review page.</p>
<p>The rule of thumb is simple. If a page makes a promise about money that you cannot demonstrate, rewrite the promise. If a page exists to move a visitor to an affiliate link, make sure it also teaches them something.</p>
<h2>Three: low value navigation and advertising clutter</h2>
<p>Many sites pass the content bar and fail on the experience bar. A wall of ad slots between the header and the first paragraph, a mobile layout that pushes the text off the screen, a cookie banner that covers half the viewport, pop ups that appear on arrival. The reviewer sees a site that is built to extract value from a visitor, and that is the thing they are screening for.</p>
<h2>Four: copyright and copied material</h2>
<p>Scraped articles, rewritten Wikipedia, and text copied from other sites are all visible to a reviewer who reads two pages. So is a translation of someone else's article, even when the translation is technically good. And a site that republishes a large number of listings, reviews or prices without permission can fail the same test.</p>
<h2>What to do about it</h2>
<p>Run the audit and read the policy section rather than the content section first. It is the fastest way to find the thing that is actually costing you the application. Fix the blockers, then read the content findings. Most sites that clear the policy bar clear the content bar in the same month, because the policy problems are usually structural rather than editorial.</p>
HTML,
            ],
            [
                'title' => 'E-E-A-T is not a score you can optimise, it is a habit you can build',
                'slug' => 'eeat-is-a-habit',
                'excerpt' => 'Google will not publish an E-E-A-T score. What it does publish is a set of patterns it uses to judge whether a site deserves to be trusted. You can build those patterns.',
                'meta_title' => 'E-E-A-T explained for publishers who want to pass review',
                'meta_description' => 'What E-E-A-T actually means for an AdSense review, which signals a reviewer can observe, and the practical habits that build them.',
                'tags' => ['trust', 'strategy'],
                'body' => <<<'HTML'
<p>E-E-A-T is the most quoted and least understood term in publishing. It stands for experience, expertise, authoritativeness and trust. Google has never published a score for it, so every explanation of it is a description of signals instead. That is good news, because signals can be built.</p>
<h2>Experience</h2>
<p>Experience shows up in the details. The person writing about pressure washing has pressure washed something and can tell you what the first hour felt like. The person writing about choosing a hosting provider can tell you what broke, not only what worked. A reviewer reads for the difference between a description and a report, and so does a reader.</p>
<p>The practical version: if you have not done the thing, say so, and write about what you have done. Publishers who are careful about this end up with more credible sites, not weaker ones.</p>
<h2>Expertise</h2>
<p>Expertise is narrower than people think. It is not a qualification, it is a body of knowledge in a specific area. The signal is coverage. A site that has written a hundred careful posts about one narrow subject reads as expertise far more strongly than a site that covers everything shallowly.</p>
<h2>Authoritativeness</h2>
<p>This is the hardest one, and the most delayed. It comes from other people recognising the site: mentions, links, citations, a real presence in the field. Nothing you do on your own site creates it directly. What you can do is give it a surface to attach to, which means named authors with a real history, and a site that other people can link to with confidence.</p>
<h2>Trust</h2>
<p>Trust is the one reviewers test directly, and it is the most fixable. It comes from the things a reader would need in order to feel safe: a contact route that a person answers, a privacy policy that describes what actually happens, an about page that explains who is behind the site, transparent disclosure of anything commercial, and an editorial policy that says how articles are made.</p>
<h2>The habit version</h2>
<p>None of this is a single action. It is a set of habits: name the author on every post, keep the about and contact pages one click away, disclose anything that pays you, publish when you have something to say rather than to hit a quota, and correct what you get wrong in public.</p>
<p>None of that is a trick, which is exactly why it works. The sites that pass review are not the ones that gamed the check. They are the ones that a person had built, and a reviewer can tell the difference in about a minute.</p>
HTML,
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::updateOrCreate(
                ['slug' => $post['slug']],
                array_merge($post, [
                    'status' => 'published',
                    'published_at' => now()->subDays(count($posts) - array_search($post, $posts, true)),
                    'author_id' => $author?->id,
                ])
            );
        }
    }
}
