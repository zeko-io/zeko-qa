=== Zeko QA ===
Contributors: zeko
Tags: qa, questions, answers, community, knowledge
Requires at least: 5.8
Tested up to: 7.1.2
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Q&A engine with a custom database architecture, algorithmic sorting, bounties, reputation, and Zeko ecosystem integration.

== Description ==

Zeko QA is a community question and answer engine on a custom database schema. It provides algorithmic question sorting, community voting, bounties, and reputation, closely integrated with the Zeko ecosystem.

= Features =

* **Ask & Answer** — Rich-text question and answer editor (Quill)
* **Voting & Comments** — Upvote/downvote answers and comment threads
* **Accepted Answers** — Question authors accept the best answer
* **Bounties** — Attach a bounty to a question to reward a good answer; unclaimed bounties expire and refund
* **Reputation & Badges** — Reputation system with answer badges
* **Topics, Tags & Spaces** — Organize content by topic, tag, and space
* **Personalized Feed** — Algorithmic question sorting for each user
* **Bookmarks** — Save questions for later
* **Notifications** — In-app notifications for answers, accepts, and trending questions
* **Ecosystem Integration** — Activity feed logging, profile cards in the AI profile hub, and reward hooks

= Shortcodes =

* `[zeko_qa_archive]` — Question archive with sorting and filters
* `[zeko_qa_ask_form]` — Ask-a-question form
* `[zeko_qa_dashboard]` — User Q&A dashboard

== Installation ==

1. Upload the `zeko-qa` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create pages and add the shortcodes above

== Frequently Asked Questions ==

= How do bounties work? =

Users attach reputation points to a question. When the author accepts an answer the bounty is awarded to the winner; questions left open past their expiry date are marked expired and the bounty is refunded.

= Is there a reputation system? =

Yes. Upvotes and accepted answers build reputation, and committed bounties are deducted so reputation cannot be double-spent.

== Dependencies ==

Zeko QA works standalone. Zeko Core (when active) enables shared activity-feed logging and helper utilities; Zeko Rewards (when active) listens to QA reward hooks.

== Privacy ==

Data stored includes questions, answers, comments, votes, topics, spaces, reputation log entries, badges, and bookmarks. Activity-log rows store an IP address when an action is logged through the ecosystem; unauthenticated AJAX is rate-limited by IP.

== External Services ==

Zeko QA makes no outbound network requests of its own. See
`REMOTE-SERVICES.txt` in this plugin folder for the full statement.

== Uninstall ==

Deleting the plugin drops all Zeko QA tables, removes its options and the pages it created (marker-verified via Zeko Core when available), and deletes Q&A-specific user meta (`zeko_qa_expertise`).

== Troubleshooting ==

* Bounty not awarded? An answer must be accepted by the author before the bounty transfers; open questions past their expiry are marked expired and refunded.
* Vote not counting? Users can only vote on content once — check the vote history.
* Question listing stale? Personalized feed sorting is per-user; verify the algorithmic sort settings.

== Third-party resources ==

The ask/answer rich-text editors are Quill Editor 1.3.7 (BSD 3-Clause). The
Quill files are NOT copied into this plugin — they are enqueued from Zeko Core
(`Zeko_Core_Assets::enqueue_quill()`) and live in
`plugins/zeko-core/assets/vendor/quill/`. Zeko Core is the single distributor
of Quill and documents it in `plugins/zeko-core/THIRD-PARTY-NOTICES.txt`;
this package bundles no other third-party code (all other assets are original
GPL-2.0-or-later work; the only image is the screenshot).

== Screenshots ==

1. Community Q&A dashboard with hot questions


== Changelog ==

= 1.0.0 =
* Initial release
* Custom Q&A database architecture with algorithmic sorting
* Ask/answer, voting, comments, accepted answers
* Bounty system with expiry and refund
* Reputation and badges
* Topics, tags, and spaces
* Personalized feed and bookmarks
* Notifications and ecosystem integration

== Upgrade Notice ==

= 1.0.0 =
Initial release of Zeko QA.
