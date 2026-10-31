# PostIt — Business Logic

## Additional info

["some text"] - stands for features that will be implemented in future 

## 1. About PostIt

PostIt is a web application where people share their ideas and problems and get solutions or opinions from other users, in a simple but effective discussion format. It is also a place to spend time without contributing anything — reading posts and discovering groups.

**Audience.** Everyone. A group can be about absolutely any topic — from cooking to games, from games to programming — as long as it does not violate basic human taboos.

**Why PostIt.** It answers problems of existing platforms such as Reddit:
- *Moderation.* Moderation is transparent and anonymous at the same time. [A moderator acts anonymously: moderation actions are not signed with their username, and their own account looks like a regular user's], so they cannot be targeted and harassed, while their actions are open and [logged]. Moderation decisions [can be appealed], and a moderator [can be removed by a cross-group "court"].
- *Search.* On Reddit it is easier to find something through Google than through the site itself. PostIt's own search must not have this problem.
- *No auto-translation.* Posts are never machine-translated: a translation cannot convey the thought of a post.

**Languages.** A group chooses its own language, and its members write in it. People who search using that language's characters find the group and can easily join the conversation. The interface does not have to be translated into every language: people who are online regularly already know English.

**Monetization.** [Advertising banners] are acceptable, since hosting cannot be paid for otherwise. [Users can support the project with one-time donations via PayPal.]

**Out of scope.** No paid content and no sponsorships, as other sites do — that goes too far.

**Core principles** — not given up under any circumstances (the list may grow):
- Anonymous social network to discuss
- Transparent moderation process
- Auto-selection of moderators
- wide-range of possibilities of what you can discuss, platform itself has very little amount of rules 

## 2. Registration and Authentication

**Registration.** A person registers with a username, an email, a password and a date of birth. Username and email are unique.

**Email and username.** The email is the login — a user signs in with the email only, never with the username. The username is the user's anonymous public name on the network.

**Date of birth.** Required out of concern for the safety of underage users: it is the simplest way to at least somehow confirm age. There is no minimum age; what matters is adulthood (18+). Only an adult can enable adult content in settings; [for minors such content is filtered or cut off entirely].

**Languages.** At registration, the user's speaking languages are set automatically from the browser and can later be changed in settings. They directly affect the content the user sees: every group has a fixed language, and its posts are written in that language.

**Email verification.** The email is the key to account recovery: an unconfirmed email may contain a typo or belong to someone else, and then a password reset link would never arrive. Therefore:
- After registration the user is signed in, but [until the email is confirmed they have no access anywhere except the verification page — reading included — and are reminded to confirm on every sign-in].
- [An account whose email is not confirmed within one day is deleted.] [The verification email and the verification page warn about this.]

**Account changes.**
- [The username can be changed, but not more often than once a week, so it cannot be changed frequently. A released username can be taken by someone else.] [On a username change, a notification message is sent to all of the user's followers; for everyone else the change is of no concern.]
- The password can be reset through "forgot password", and [changed by a logged-in user in settings, with no period limit].

**[Password fields have an "eye" toggle that shows the typed characters.]**

## 3. Guests and Users

**Guest.** A convenience for people who do not want an account — for example, someone who found a solution through Google and wants to read it, but not write or vote. A guest sees private groups like everyone else and can find them through search, but their posts do not exist for a guest: getting in requires registration.

**Profile.**
- *Status* — what the person is doing right now, in a couple of words.
- *Bio* — what the person has been doing and interested in for a long time.

**Karma.** [A global rating shown on the profile: how much the user has contributed to PostIt.] The basis is how other people treat you, so karma comes only from votes on the user's posts and comments: an upvote adds 1, a downvote takes 1 away, and a post and a comment weigh the same. Karma can go negative — e.g. for writing off topic. Moderation does not count toward karma. [Account karma is a counter of its own, kept apart from sub-karma: a vote changes both, but neither is computed from the other.] Karma is general, not tied to any topic: a person who writes about cooking has no reason to write about programming.

**Sub-karma.** [Each group has its own sub-karma for each user, earned the same way from votes on their posts and comments in that group. It is the user's long-term authority in the group: it shows where a person earned their karma, it is one of the two keys to the moderator candidate pool, and it is the key to a free owner's seat (see Roles).] [Sub-karma is shown on the user's profile, next to each of their groups, but never next to their posts and comments: people should not judge a person — and vote — by their karma.] Others *could* guess from it who the moderator is, but hardly *would*: sub-karma is only one of the two keys, and the other one, activity, is not shown anywhere (see Roles → Becoming a moderator).
- [Anyone who can comment in a group earns sub-karma there, member or not. Leaving the group keeps it — otherwise it could be abused.]
- [Karma is earned authority, so it is never lost with its source: an author keeps the karma their content earned when they delete it, and everyone keeps the karma earned in a group when the group is deleted.]
- [The one exception is a moderation case that finds the content broke the rules: then the content counts as if it had never been posted, and whatever it earned is undone — taken back if it was positive, given back if it was negative. Only its author is affected: replies and comments under it keep theirs.]

**Activity.** [Besides sub-karma, each user has an activity score in each group: the same votes on their posts and comments there, but only those of the last 30 days. It is a rolling window, not a counter reset every month, so it never jumps to zero for everyone at once. Sub-karma tells whether the group trusts a person; activity tells whether they are around now. It is the other key to the moderator candidate pool (see Roles), so that the role goes to people active now and not only to old accounts.]

**Counters.** [Counters of the user's actions are collected.] They work two ways: as statistics visible only to the user themselves, and as the basis for public achievements (see Gamification) — e.g. "most active voter" for 1000 votes cast. Some counters (e.g. reports sent) stay statistics only, with no achievement.

**Following.** Following a user is purely tracking their activity — it is not friendship or anything like it. [A follower counter is planned; how exactly it works is not decided yet.]

**Blocking.** [A user can block another user; after that they no longer see the blocked user's messages or activity. Blocked content is shown like deleted content, with "User is blocked — view anyway?"] Blocking is anonymous: the blocked user is never told.

**Settings.**
- *Swear words.* [Swear words are masked with asterisks and revealed on click.] They are shown right away only if the user is an adult and has enabled showing them. [On a minor's account the swear-words setting is disabled, the same as adult content.]
- *Direct messages.* The user either allows or forbids [direct messages] from all users.

**Notifications.**
- [There are two categories: *Default* and *Important*. Important notifications appear differently, so they stand out among the rest.]
- [Whether a notification makes a sound is decided by the server at the moment it is created. A notification that came in silently stays silent; un-muting affects only the ones that come after.]
- [*Following* decides whether notifications come at all; a mute only decides whether they make a sound. A post's author follows it from the start, and anyone else can follow it with an "interested" button. A comment's author follows the replies to it from the start. Both can stop following.]
- [Notification settings take two pages:]
  - [*Kinds.* Each kind of notification has one of three states: with sound, silent, or off. An off notification is not kept or sent at all. Important kinds cannot be turned off, only silenced — a warning or a ban always arrives.]
  - [*Groups.* The groups, posts and comments the user has silenced. It works as a hierarchy — group → post → comment: everything that comes from a silenced group is silent, and a post inside it cannot be left with sound.]
- [All notifications can also be silenced at once — until a chosen time, or until the user turns it back on.]
- [If a post or comment a notification points to is deleted, the notification stays and shows it as deleted, the same as everywhere else.]
- [Direct messages are not notifications — they have their own tab.]

Kinds of notifications, by role:
- *Every user.* [A followed user changed their username — showing who changed it and to what.] [A new follower.] [A new post by a followed user; a new post in a followed group.] [A join request to a private group was accepted or declined — when joining by link is not allowed.] [A new comment or reply under a post you follow.] [A reply to your comment.] [One comment brings a person at most one notification: a reply to your comment comes as a reply, not also as a new comment under the post. If replies are turned off, it comes as a new comment instead — if you follow the post.] [Feedback on a report you sent: the result, e.g. the offender was punished or found not guilty. The reported user is not told about the report.]
- *Every user, Important.* [A warning, and its removal; administrators give owners a warning first. Moderators will also give warnings within a group.] [A ban and an unban, in a group or on the whole platform — an unban is an important event too.]
- *Moderator.* [An invitation to the role, when the user is picked among the many who crossed the threshold (Important).] [A welcome on first taking the role after accepting.] [New moderation cases, such as reports sent by users, and closed cases.]
- *Administrator.* [A welcome on taking the role.] [New cases and closed cases, the same as for moderators but on the administrator's level.]

**Interface tours.** The audience is everyone, older people and adults above all, so the interface explains itself. Hover hints do not work on phones, and a forced walkthrough is unpleasant, so instead:
- [The first time a user opens a page that has a tour, they are asked whether they want a short tour of it. The answers are "Yes", "No" (never ask again) and "Later" (ask on the next visit).]
- [A tour shows its steps one by one, each with a short explanation and a "Continue" button; it can be skipped at any step. It works the same on every platform.]
- [Each tour is answered separately: finishing one does not count for another.]
- [When a page has several tours not answered yet (the navigation one is always among them until answered), they are offered with one prompt and run one after another, so the user is never asked twice in a row. The answer counts for each of them. The account menu always runs the full tour of the current page.]
- [Guests never see tours: a guest is often someone who came from Google to read one thing, and nothing should get in their way.]
- [The tours, each explaining what is on its page:]
  - [*Navigation* — offered on whichever page the user lands first, since the navbar is on every page: the home button and the account menu; search; messages and notifications.]
  - [*Feed* — the Following and Recommended tabs and their filters; the post card: what the title, the author and the group lead to; the counters.]
  - [*Group* — joining, and what is different in a private group; the rules, which differ from group to group and must be followed; creating a post.]
  - [*Post* — the text; voting; commenting and replying.]
  - [*Own profile* — the status and the biography; editing the fields and the avatar; achievements.]
  - [*Someone else's profile* — who the person is (status, biography); following them; achievements.]
- [The post card and the counters are explained only in the Feed tour, so the other tours do not repeat them.]

## 4. Groups

**Creation.** Any verified user can create a group and becomes its owner. There is no limit on the number of groups — only a throttle, so that group creation cannot be used for spam.

**Group address.** Every group has a unique address (slug): lowercase Latin letters, digits and hyphens. Like a username, it is a key identifier: to get into a particular group, nobody has to type unfamiliar characters — the identifier is understandable to everyone. The group's name can be in any language.

**Owner's authority.** [The owner can change the group's language, name, description, rules and address — the address not more often than once every 7 days.] Before doing so they may hold a poll among members, but nobody obliges them to.

**Private groups.**
- [A group can be switched between public and private. When a public group becomes private, everyone currently subscribed automatically becomes a member.]
- A private group is joined through a join request. [At first, requests are reviewed by the owner. A new group setting can additionally allow all members to share an invite link that lets people in directly. Without that setting, members can still copy a link to the group's join request.]
- [The reviewer can either reject a request, or reject it and block further requests from that user — both with a reason. This action is throttled.]

**Rule history.** The group's rules are stored as versions; a change creates a new version instead of overwriting the old one. This is a top-level requirement for transparent moderation: the goal of PostIt is that nobody is ever punished for nothing. [When moderators review an appeal, they judge the decision against the rule version that was in force when it was made, not against the current one — rules may have changed since, and the moderator acted on the old ones.]

**Deleting a group.** [The owner can delete the group, together with all of its posts.] Its pending reports and appeals go with it: a ban in a group that no longer exists no longer matters to anyone. [The karma earned in the group stays with its users (see Guests and Users → Sub-karma).]

A lot depends on the owner, so it matters that the right person gets the role. If the owner turns out to be the wrong one, people will create an alternative group and move there — for example, by a link left by the new group's owner.

## 5. Roles: Owner and Moderator

**Owner anonymity.** [Nobody knows who the owner is, just as nobody knows who the moderators are: the group shows no owner, and the owner's actions are not signed with their username. The owner can moderate, especially early on, and a known owner would give the moderators away too.]

**Handing over ownership.** [An owner who wants to leave must hand the role to one of the group's moderators. Since everyone is anonymous, it works as a "hot potato": the first moderator to say "yes, I want it" becomes the owner. If the owner deletes their account, the same happens automatically. If the group has no moderators, or none of them says yes, the owner's seat becomes free and any member of the moderator candidate pool — both thresholds crossed, see Becoming a moderator — can take it with a click and a confirmation. Nobody becomes the owner without taking the seat themselves, and the activity threshold keeps an abandoned old account from taking it: taking part in a group means getting votes from it, not just writing.]

**Moderation is a right, not a job.** [Nobody does work for nothing, and a role handed out as a duty — with cases assigned and idleness punished — would not hold. So moderation is a right that many trusted members of a group get, and they moderate when they want to. There are many such people in a group, which is why the work gets done without anyone being made to do it — but not everyone, or everyone would rule.]

**Becoming a moderator.** [A member enters the group's candidate pool when both their sub-karma and their activity cross a threshold: a good history in the group alone is not enough without being active now, and a month of activity alone is not enough without a good history. Nobody sets the thresholds: they are computed automatically, so the owner cannot tune them to let particular people in or keep them out. The sub-karma threshold is a share of the group's member count (e.g. 10%). The activity threshold is 2% of all the votes cast in the group in the same 30 days — since each vote goes to one author, at most about 50 people can cross it. It follows how alive the group really is and does not grow with members who never vote. Neither threshold is ever below 1.]
- [About half of the pool, chosen at random, gets the moderation rights; the other half stay trusted members without them. Those who are quarantined or barred (see Moderator reliability) and those who said they do not want the rights (see Leaving the role) are left out of the draw. There is nothing to decline: a right one does not want is simply not used.]
- [The rights are handed out in waves, once a week. A wave counts the group's moderators, and if there are fewer than half of the pool, it gives the rights to enough random pool members to make up the difference.]
- [A wave only recruits; it never takes the rights away. A moderator who drops out of the pool keeps them — moderating brings no votes, so someone busy with cases rather than posts would otherwise lose the role for doing the work; and thresholds grow with the group, so a person can drop out without changing anything. Nobody is thrown out for working little: the rights are lost only in the ways under Leaving the role, and those take effect at once, not at the next wave. Likewise, if the pool shrinks and moderators become more than half of it, the wave simply adds nobody.]
- [A new group has nobody yet who knows what they are doing, so there is no early wave for it: until the group gathers an active pool, the owner moderates.]
- [Being in the pool does not give a moderator away: only about half of the pool moderates, and while sub-karma is visible on the profile, activity is not, so nobody can tell from the outside even who crossed both thresholds.]

**Moderation cases.** [A case opens when enough members report the same post or comment:]
- [Only reports from members with some reputation in the group count — their sub-karma must be above a threshold — so bots and fresh accounts cannot report content away. The number of reports that opens a case is computed automatically from the group's activity, like the pool thresholds, and is never below 3.]
- [Once a case opens, the content is hidden until a decision is made. Hiding is not a punishment: nothing is decided yet. It keeps the decision objective — in a small group a moderator could otherwise recognize the post from the feed — and keeps moderation fast.]
- [A moderator sees the content and the reports, but not the author's username: the decision is about what was written, not about who wrote it. Reports are not anonymized for moderators, so a group of people who keep reporting together without cause can be noticed.]
- [Cases are not assigned. They wait in the group's queue, and any moderator who comes takes one.]
- [A case nobody takes within 24 hours goes to the owner; if the owner does not take it within another 24 hours, it goes to a platform administrator.]

**Moderator reliability.** [A moderator has a reliability score inside the group, separate from sub-karma. It measures only how *correct* the moderator's decisions are — not how active they are: it drops when an appeal against their action succeeds, since that means the decision was wrong.] It is kept separate from sub-karma so that, since sub-karma is visible, falling reliability cannot be seen by others. Other users' opinion of the moderator's work does not affect whether the moderator keeps the role.
- [Reliability is hidden from everyone, the moderator included. Seeing it change, a moderator could work out what moves it and play to it — in a good way or a bad one.]
- [A new moderator starts at 100. While it stays above zero, they are a moderator; once it falls to zero, the system takes the role away.]
- [A moderator who loses the role this way cannot get back into the group's candidate pool for at least 30 days. One who loses it a second time is barred from the role in that group for good. Both apply only within the group.]

**Leaving the role.** [There are three ways out:]
- [*Stepping down.* A moderator can press "I'm not interested" at any time, without any penalty.]
- [*Inactivity.* A moderator who has not handled a single case for 30 days while there were cases in the queue loses the rights automatically, without any penalty. It removes dead accounts and people who do not moderate when there is work, at once.]
- [*Wrong decisions.* Reliability falls to zero, with the quarantine or the bar (see Moderator reliability).]
- [Stepping down and inactivity both set a "don't want to moderate" flag: the person is left out of the draw for the rights, even on re-entering the pool, until they clear the flag in the group's settings. The flag shows up there only for someone who has had the rights at least once.]
- [Their reliability is frozen: if they moderate in the group again, they start from where they left off, not from 100. Otherwise stepping down just before losing the role would wash the record clean.]

**Accountability.** A moderator's action is never put to a crowd vote in its own group — otherwise the people just sanctioned could out-vote their own sanction. An action can be overturned only through an appeal, judged by a small jury drawn at random from *other* groups.

**Warnings.** [A moderator can give a member a warning for breaking the group's rules. Warnings work like strikes: they accumulate, and too many of them put the member at risk of a punishment in the group.]
- [A warning is a moderation action like any other: it is tied to the content and the rule that was broken, it appears in the moderation log, and it can be appealed (see Appeals).]
- [Each warning cools down on its own: the moderator sets its cooldown period when giving it, and when the period runs out, the warning disappears. A warning also disappears if an appeal overturns it.]
- [The group set the maximum number of active warnings and the bounds for a cooldown period. The platform validates these bounds too, so an absurd period, such as 10 years, can never be stored.]
- [Reaching the maximum does not punish the member automatically: it opens a separate moderation case. The moderator who takes it either closes it, if they find no punishment is deserved, or chooses a punishment in the group: a ban or a mute (the member can read but not contribute), each temporary or permanent. The decision, either way, resets the member's warnings. It is a moderation action too, so it can be appealed.]
- [The case goes into the group's queue like any other (see Moderation cases), and the member's username is hidden there too, so that a decision is not driven by built-up feelings about a particular person.]
- [Warnings are separate from sub-karma: they do not lower it and do not affect the candidate pool. Their only consequence is the risk of a punishment.]

**Moderation log.** [Moderator actions are logged, and the log is open to everyone, so a moderator's work is transparent.]
- [The log is generated automatically — a moderator does not write it, so it cannot be embellished, and writing it by hand every time would take too long.]
- [The removed content is shown in the log, but not in a prominent place — on a separate moderation tab. The author's username is hidden.]
- [An entry appears in the log one day after the removal, so it is not obvious whose post was removed, even if someone saw it and remembered.]
- [An entry shows the same as an appeal: the content, the rule that was broken, and the rule version in force at the time — so anyone can judge whether the moderator acted fairly.]
- [An entry names no moderator — neither a username nor a stable pseudonym. The log shows what was done, not who did it: nobody needs one moderator's history, and a name would only help to find out who they are.]

**Appeals.** [Everything a moderator does that can be appealed is reviewed by a jury:]
- [Only the user affected by an action may appeal it, and only that specific action — not a general complaint against the moderator.]
- [The jury is drawn at random from *other* groups. First it is 2 moderators and 1 pool member without the rights; the appeal succeeds only if all 3 agree. If anyone disagrees, 1 more moderator and 1 more such pool member join, and the appeal then needs 4 votes of 5. By default the moderator's action stands: overturning it takes near-unanimous agreement.]
- [Pool members without the rights sit on the jury alongside moderators. They are not "the crowd": there are only one or two of them, drawn at random from other groups, and they crossed the same thresholds as the moderators — the random draw just did not give them the rights.]
- [Voting is blind: a juror sees the others' votes only after everyone has voted. A vote cannot be cast without picking the rule that was or was not broken and a short reason.]
- [Nobody is named in an appeal: the moderator whose action is appealed is shown as "the appealed moderator", the jurors as "Juror 1", "Juror 2", and so on.]
- [The appeal is judged against the rule version in force when the action was taken (see Groups → Rule history).]
- [While an appeal is pending, the removed content stays hidden.]
- [Jury duty pays nothing — no karma, no currency: rewarding each vote turns a jury into a click farm. The reward is knowing that a fair vote keeps the place safe for oneself and for others. The juror is shown it with every case — "Your fair vote protects the system from unfair punishments" — and is told how the case ended once it closes, so the vote is seen to matter.]

**Juror trust.** [Each juror has a hidden trust score; nobody sees it, the juror included. It is computed automatically, so it does not load the administrators:]
- [*Control cases.* Now and then a juror gets an already closed appeal from another group, anonymized and looking like any other case. Only the clearest ones are reused: a unanimous verdict of 5, or a first-stage verdict nobody took further. A juror who keeps getting them wrong is voting at random. If many jurors in a row disagree with a control case, it is taken out of use and goes to an administrator, in case the original verdict was wrong.]
- [*Agreement over time.* Since voting is blind, a careful juror agrees with the final verdict much more often than a random one. It is counted over many cases — disagreeing in one case is never punished, or jurors would be afraid to vote against the others.]
- [A juror with low trust is no longer called to juries. That is the only consequence: reliability measures the moderator's own decisions in their group, not their jury work.]
- [Administrators look only at the jurors who stand out, so their workload follows the number of careless jurors, not the number of cases.]

**Owner accountability.** The owner is not judged by the jury; in practice the owner soon stops moderating and leaves it to moderators. [When an anonymous owner's action is appealed, a platform administrator is notified. The history of the owner's violations is kept, including whether they were objectively groundless. The administrator judges by the nature of the violations rather than their count, and decides at their own discretion whether the owner is guilty. Ownership can be transferred by force only after the owner has received at least one warning.]

**Polls.** [A member can hold a poll in a post (e.g. about changing the rules or topic) — it is still just a post (see Posts and Discussions). A poll does not bind the owner, but the owner responds to it publicly.]

**Set aside: moderation as a job.** *Not planned — an earlier model, where moderation was a duty, kept here for reference in case parts of it are needed again.*
- The role was offered to a *random* candidate from the pool, who could decline; the group's workload decided how many moderators it had: when, over a day, more than N cases per hour (set by the owner, 2 by default) came in for each moderator, the role was offered to another candidate.
- Each case went to a random moderator. Leaving it idle longer than the owner allowed (12 hours by default) lowered reliability.
- A moderator could pass a case to another random moderator; the owner set how many free passes a day (0 to 3), and every pass beyond that lowered reliability. If every moderator passed, the case went around again.
- Careless jury work lowered a moderator's reliability too.

## 6. Posts and Discussions

**Posts.**
- The title is optional: it is needed when the text is long, but a short text or a simple question does not need one.
- The text is written in Markdown. [Its length is limited to 5000 characters.]
- There are no post types - user is not binded to system limitations and can write in any way he want either a question or topic etc.

**Comments.** Comments are up to 500 characters and form threads. [Nesting is limited to 5 levels, so a thread cannot grow out of the interface.]

**Editing and deleting.**
The same rules apply to posts and comments.
- [A post or comment can be edited; it is then marked "(edited)".]
- [A post or comment can be deleted by its author. It does not disappear — there may be comments under it. Instead, its author, title and text are replaced with a "deleted" placeholder.]
- [Content removed by a moderator is shown as "hidden" — distinct from "deleted".]

**Sharing.** One button copies the post's link — simpler than right-click and copy. It also feeds statistics: the system remembers who shared, so one user cannot inflate the share count by copying again and again.

**Random post.** A group has a "random post" button for those who want to open a random post from it. [The same is planned for the home page: a random post from a random group — you may find something interesting. Adult-content filtering applies.]

**Direct messages.** [Asynchronous, mail-like messaging: a message has a subject and a body.] Not a chat — a chat would be a secondary feature used a couple of times and forgotten, so it is not worth the effort; but a user must at least be able to send a simple message. [A message can optionally reference a group, so the reader understands what it is about — e.g. you own a group and someone writes to you about it.]

## 7. Voting

**Casting a vote.** A user has one vote per post or comment: up or down. Voting the same direction again removes the vote; voting the other direction switches it. A user cannot vote on their own content, on deleted content, or on content they cannot see.

**Hidden counts.** Exact upvote and downvote counts are never shown — to avoid hate and the herd effect. [The client never receives them either: the server sends only the ready score (upvotes minus downvotes).]

**Controversy badge.** Marks a post or comment that is contested and needs attention — not because of its number of likes or its newness, but because of this parameter. It is computed by the server and appears only once both sides have at least 3 votes. The value is rounded, so it gives only an approximate estimate; going further to protect exact counts is not worth the effort — the badge already does its job, and someone who doesn't know how it works won't understand anything from it.

[Votes received also count toward the author's karma (see Guests and Users).]

## 8. Feed and Search

**Recommended feed.** Made for finding something new: it shows posts from public groups the user is not subscribed to, uses filters and adapts to the user. It leans toward the new rather than the best: posts from the last 7 days come first, then those up to 30 days old, then older ones; within each of these, posts with a higher score come first. Posts in the user's languages come first, so the user sees everything available in them; once those run out, posts in other languages follow.

**Following feed.** Posts from the groups the user is subscribed to, or from the people they follow. A guest is asked to log in.

**Filters.** "Only new" (posts the user has not opened or voted on), "only my languages", and a period: week, month or all time.

**Search.** A couple of words should be enough to find what you need. A group is found by typing its name in its own language; a post is found by words that may appear in its title or text. A person searches for their problem and finds a post about it. A private group is found like any other, but its posts are locked: the group is shown first, so the user can send a join request.

## 9. Site Administration

**Who an administrator is.** Part of the development team — a vetted person, so there is no need to worry about how they do their job. Ordinary users cannot get this role: [it is granted through a database query]. There should always be more than one administrator.

**Purpose.** The role exists purely to resolve cases with users, so an administrator has no reason to post or create groups. [An administrator is a regular user with an admin panel rather than a bare panel: they can act on reports, but also on their own initiative when they notice a violation while browsing the feed.]

**Anonymity.** [An administrator can have an ordinary username, and nothing on their profile says they are an administrator.] An administrator does not see who is behind a moderator's mask, just as a moderator does not see the administrator's name. *Open question:* administrators are created by the developers, so they are close people or at least acquaintances of each other — anonymity between administrators may not hold; this needs more thought.

**Boundaries.** An administrator acts only within platform rules. They cannot decide that someone deserves a ban for breaking a group's rules — that is for the group's moderators.

**Reports.** [Reports made outside groups go to administrators. Moderators escalate to administrators reports that are critical: a violation of platform rules, or a user who is an active violator and deserves to be cut off from the platform.]

**Sanctions.** [Both can be permanent or temporary; the term is decided by the administrator according to rules set by the site's management.]
- [*Ban* — the user cannot log in to the site with their account.]
- [*Mute* — the user cannot contribute anything: no posts, no comments, nothing — they can only view.]
- [Rarely, deleting posts or comments for breaking platform rules.]

**Appeals.** [A user can appeal an administrator's decision, so that another administrator reviews the case.]

**Platform rules.** [Currently not shown anywhere. They can be shown after registration and then kept behind a button.]

## 10. Gamification

**Achievements.** [An achievement is not necessarily "reach a certain amount of something": there are also one-off ones, such as "first post" or "a year on the platform".] [Counter-based achievements are built on the user's counters (see Guests and Users).]

**Showcase.** [Nobody forces a user to put everything on display: the user chooses which achievements are shown on their profile showcase.]

**Currency.** [Visual items are obtained for an on-site currency. One option is daily tasks that reward currency — e.g. make a post that collects a certain number of likes, views, etc.] The details are still to be thought through.

**Shop of visual items.** [Many things can be bought: avatar frames, profile backgrounds, comment styling, custom emoji with game crossovers, and more.] Styling matters: a site that looks dull and uniform can turn into too serious social network — items add uniqueness.
