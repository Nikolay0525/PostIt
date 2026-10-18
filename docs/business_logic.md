# PostIt — Business Logic

## Additional info

["some text"] - stands for features that will be implemented in future 

## 1. About PostIt

PostIt is a web application where people share their ideas and problems and get solutions or opinions from other users, in a simple but effective discussion format. It is also a place to spend time without contributing anything — reading posts and discovering groups.

**Audience.** Everyone. A group can be about absolutely any topic — from cooking to games, from games to programming — as long as it does not violate basic human taboos.

**Why PostIt.** It answers problems of existing platforms such as Reddit:
- *Moderation.* Moderation is transparent and anonymous at the same time. [A moderator acts under an anonymous mask: moderation actions are not signed with their username, and their own account looks like a regular user's], so they cannot be targeted and harassed, while their actions are open and [logged]. Moderation decisions [can be appealed], and a moderator [can be removed by a cross-group "court"].
- *Search.* On Reddit it is easier to find something through Google than through the site itself. PostIt's own search must not have this problem.
- *No auto-translation.* Posts are never machine-translated: a translation cannot convey the thought of a post.

**Languages.** A group chooses its own language, and its members write in it. People who search using that language's characters find the group and can easily join the conversation. The interface does not have to be translated into every language: people who are online regularly already know English.

**Monetization.** [Advertising banners] are acceptable, since hosting cannot be paid for otherwise. [Users can support the project with one-time donations via PayPal.]

**Out of scope.** No paid content and no sponsorships, as other sites do — that goes too far.

**Core principles** — not given up under any circumstances (the list may grow):
- Exact upvote and downvote counts are hidden.
- Rectangular visual style.

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

**Karma.** [A global rating shown on the profile: how much the user has contributed to PostIt. The contribution is assessed by other users — votes on the user's posts and comments, and so on.] Moderation does not count toward karma. Karma is general, not tied to any topic: a person who writes about cooking has no reason to write about programming.

**Sub-karma.** [Each group has its own sub-karma for each member; its purpose is to get the member into the group's moderator candidate pool (see Roles).] Whether it is visible to others is not decided yet. Even if it is, others *could* guess who the moderator is, but hardly *would*: the threshold may be low, and candidates may number in the hundreds or thousands.

**Counters.** [Counters of the user's actions are collected], but stored only in the database — users never see them directly. They are the basis for achievements (see Gamification): e.g. "most active voter" for 1000 votes cast.

**Following.** Following a user is purely tracking their activity — it is not friendship or anything like it. [A follower counter is planned; how exactly it works is not decided yet.]

**Blocking.** [A user can block another user; after that they no longer see the blocked user's messages or activity. Blocked content is shown like deleted content, with "User is blocked — view anyway?"] Blocking is anonymous: the blocked user is never told.

**Settings.**
- *Swear words.* [Swear words are masked with asterisks and revealed on click.] They are shown right away only if the user is an adult and has enabled showing them. [On a minor's account the swear-words setting is disabled, the same as adult content.]
- *Direct messages.* The user either allows or forbids [direct messages] from all users.

**Notifications.**
- [*Default* — e.g. a followed user changed their username, or someone commented under your post.] [Each kind can be turned off separately.]
- [*Warning* — moderator warnings and bans.] These cannot be turned off: they will not arrive often.

## 4. Groups

**Creation.** Any verified user can create a group and becomes its owner. There is no limit on the number of groups — only a throttle, so that group creation cannot be used for spam.

**Group address.** Every group has a unique address (slug): lowercase Latin letters, digits and hyphens. Like a username, it is a key identifier: to get into a particular group, nobody has to type unfamiliar characters — the identifier is understandable to everyone. The group's name can be in any language.

**Owner's authority.** [The owner can change the group's language, name, description, rules and address — the address not more often than once every 7 days.] Before doing so they may hold a poll among members, but nobody obliges them to.

**Private groups.**
- [A group can be switched between public and private. When a public group becomes private, everyone currently subscribed automatically becomes a member.]
- A private group is joined through a join request. [At first, requests are reviewed by the owner. A new group setting can additionally allow all members to share an invite link that lets people in directly. Without that setting, members can still copy a link to the group's join request.]
- [The reviewer can either reject a request, or reject it and block further requests from that user — both with a reason. This action is throttled.]

**Rule history.** The group's rules are stored as versions; a change creates a new version instead of overwriting the old one. This is a top-level requirement for transparent moderation: the goal of PostIt is that nobody is ever punished for nothing. [When moderators review an appeal, they judge the decision against the rule version that was in force when it was made, not against the current one — rules may have changed since, and the moderator acted on the old ones.]

**Deleting a group.** [The owner can delete the group, together with all of its posts.] Its pending reports and appeals go with it: a ban in a group that no longer exists no longer matters to anyone.

A lot depends on the owner, so it matters that the right person gets the role. If the owner turns out to be the wrong one, people will create an alternative group and move there — for example, by a link left by the new group's owner.

## 5. Roles: Owner and Moderator

**Handing over ownership.** [An owner who wants to leave must hand the role to one of the group's moderators. Since everyone is anonymous, it works as a "hot potato": the first moderator to say "yes, I want it" becomes the owner. If the owner deletes their account, the same happens automatically. If the group has no moderators, or none of them says yes, the owner's seat becomes free and anyone who has crossed the sub-karma threshold can take it with a click and a confirmation.]

**Becoming a moderator.** [When a member's sub-karma crosses a threshold, they enter the group's candidate pool. The role is offered to a *random* candidate from the pool — never simply to the top-scoring one, so the role does not turn into a popularity race. The candidate may decline without any penalty.]

**Moderator rating.** [A moderator has a rating inside the group, separate from sub-karma. It grows with the moderator's activity and drops when they do not do the work — but only relative to the group's actual workload: no pending reports means no decay. It has to be maintained to keep the role. If an appeal shows the moderator acted without grounds, the rating gained will be cancelled entirely and even go negative.] It is kept separate from sub-karma so that, if sub-karma is ever made visible, a falling moderator rating cannot be seen by others. Other users' opinion of the moderator's work does not affect whether the moderator keeps the role. If moderator keep receiving negative rating the system will force this user to lose moderator role.

**Accountability.** A moderator's action is never put to a crowd vote — otherwise the people just sanctioned could out-vote their own sanction. An action can be overturned only through an appeal.

**Moderation log.** [Moderator actions are logged, and the log is open to everyone, so a moderator's work is transparent.]
- [The log is generated automatically — a moderator does not write it, so it cannot be embellished, and writing it by hand every time would take too long.]
- [The removed content is shown in the log, but not in a prominent place — on a separate moderation tab. The author's username is hidden.]
- [An entry appears in the log one day after the removal, so it is not obvious whose post was removed, even if someone saw it and remembered.]
- [An entry shows the same as an appeal: the content, the rule that was broken, and the rule version in force at the time — so anyone can judge whether the moderator acted fairly.]

**Appeals.** [Everything a moderator does that can be appealed is reviewed by a jury:]
- [Only the user affected by an action may appeal it, and only that specific action — not a general complaint against the moderator.]
- [The jury is drawn at random from moderators of *other* groups — 3 people, or 5 for severe cases. Voting is blind. A non-unanimous verdict escalates to a larger panel.]
- [The appeal is judged against the rule version in force when the action was taken (see Groups → Rule history).]
- [While an appeal is pending, the removed content stays hidden.]

**Owner accountability.** The owner is not judged by the jury; in practice the owner soon stops moderating and leaves it to moderators. [When an anonymous owner's action is appealed, a platform administrator is notified. The history of the owner's violations is kept, including whether they were objectively groundless. The administrator judges by the nature of the violations rather than their count, and decides at their own discretion whether the owner is guilty. Ownership can be transferred by force only after the owner has received at least one warning.]

**Polls.** [A member can hold a poll in a post (e.g. about changing the rules or topic) — it is still just a post (see Posts and Discussions). A poll does not bind the owner, but the owner responds to it publicly.]

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

## 9. Site Administration

## 10. Gamification
