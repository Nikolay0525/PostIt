# PostIt — Business Logic

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

## 4. Groups

## 5. Roles: Owner and Moderator

## 6. Posts and Discussions

## 7. Voting

## 8. Feed and Search

## 9. Site Administration

## 10. Gamification
