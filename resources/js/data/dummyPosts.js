import { dummyGroups, findGroup } from '@/data/dummyGroups';

// Dummy data shaped like the `posts` table plus the aggregates the backend will add
// (upvotes / downvotes / comments_count from votes and comments). `title` may be null.
// The feed shows an excerpt of `article`; the post page shows all of it.
const uuid = (n) => `b0000000-0000-4000-8000-${String(n).padStart(12, '0')}`;
const g = (n) => dummyGroups[n - 1].id;
const ago = (minutes) => new Date(Date.now() - minutes * 60_000).toISOString();

export const dummyPosts = [
    {
        id: uuid(1),
        group_id: g(1),
        author: { name: 'Marta Kowalska' },
        title: 'I finally finished my first Laravel project',
        article: 'After three months of evenings and weekends, my little recipe manager is live. The hardest part was not the code but deciding when to stop adding features.\n\nI started with a plain list of recipes, then added tags, then meal planning, then a shopping list generator. Each one felt small, but together they nearly made me abandon the project.\n\nWhat finally helped was writing down a "version one" list and refusing to touch anything outside it. If you are stuck in the same loop, try it.',
        created_at: ago(120),
        upvotes: 134,
        downvotes: 6,
        comments_count: 24,
    },
    {
        id: uuid(2),
        group_id: g(2),
        author: { name: 'أحمد الفارسي' },
        title: 'ما هي أفضل الطرق لتعلم البرمجة من الصفر؟',
        article: 'أبدأ الآن في تعلم البرمجة وأبحث عن مصادر مجانية ومنظمة. هل تنصحونني بالبدء بلغة بايثون أم جافا سكريبت؟ شكراً لكم مسبقاً.\n\nلدي حوالي ساعتين يومياً للدراسة، وأريد أن أبني مشاريع صغيرة بدلاً من مشاهدة الدروس فقط. أي نصيحة عن كيفية تنظيم الوقت ستكون مفيدة جداً.',
        created_at: ago(180),
        upvotes: 80,
        downvotes: 4,
        comments_count: 41,
    },
    {
        id: uuid(3),
        group_id: g(3),
        author: { name: 'Jan Nowak' },
        title: 'Sunrise over the Tatra mountains, no filter',
        article: 'Got up at 4am to catch this view and it was worth every minute of lost sleep. Sharing the route below for anyone who wants to try it.\n\nThe trail is about 9 km round trip with roughly 700 m of elevation gain. Bring a headlamp for the first hour and warm layers, because it is very cold at the top before the sun comes up.\n\nThe best spot is just past the second ridge, where the trees open up.',
        created_at: ago(300),
        upvotes: 318,
        downvotes: 6,
        comments_count: 18,
    },
    {
        id: uuid(4),
        group_id: g(4),
        author: { name: 'דנה כהן' },
        title: 'איך מתחילים ללמוד TypeScript אחרי שנים של JavaScript?',
        article: 'עבדתי שנים עם JavaScript ועכשיו הצוות שלנו עובר ל-TypeScript. אשמח להמלצות על מקורות ועל טעויות נפוצות שכדאי להימנע מהן.\n\nהפרויקט שלנו גדול יחסית, ולכן אני מנסה להבין אם עדיף להמיר הכול בבת אחת או להתקדם בהדרגה.',
        created_at: ago(480),
        upvotes: 57,
        downvotes: 3,
        comments_count: 12,
    },
    {
        id: uuid(5),
        group_id: g(5),
        author: { name: 'Oliver Grant' },
        title: 'Which text editor do you actually use every day?',
        article: 'Curious what the community really uses, not what you claim to use in interviews. Mine is embarrassingly still a plain editor with a handful of plugins.\n\nI have tried the big IDEs several times, and I always drift back because of startup time and how much they get in the way. Tell me what I am missing.',
        created_at: ago(1440),
        upvotes: 220,
        downvotes: 19,
        comments_count: 97,
    },
    {
        id: uuid(6),
        group_id: g(1),
        author: { name: 'Lena Fischer' },
        title: 'Eloquent scopes changed how I write queries',
        article: 'Moving repeated where-clauses into local scopes made my controllers half the size and my queries much easier to read.\n\nInstead of repeating where("is_public", true)->where("banned_at", null) everywhere, I now write Post::visible(). It is a small change, but it removed a whole class of bugs where one query forgot a condition.',
        created_at: ago(1500),
        upvotes: 350,
        downvotes: 8,
        comments_count: 31,
    },
    {
        id: uuid(7),
        group_id: g(1),
        author: { name: 'Tomasz Wrona' },
        title: 'Queues or scheduled jobs for sending digest emails?',
        article: 'I need to send a weekly digest to about 20k users. Should I dispatch one job per user or batch them?\n\nRight now I loop over users in a scheduled command and dispatch a job for each. It works, but memory use spikes and I am not sure how to retry failures cleanly.',
        created_at: ago(240),
        upvotes: 41,
        downvotes: 2,
        comments_count: 15,
    },
    {
        id: uuid(8),
        group_id: g(3),
        author: { name: 'Sofia Rossi' },
        title: 'Best lightweight tent under 1.5 kg?',
        article: 'Planning a week-long trek and want to keep my pack light. What are you carrying?\n\nI need something that handles heavy rain and wind but still packs small. Budget is flexible if it is worth it.',
        created_at: ago(2880),
        upvotes: 92,
        downvotes: 4,
        comments_count: 46,
    },
    {
        id: uuid(9),
        group_id: g(3),
        author: { name: 'Piotr Zielinski' },
        title: 'Autumn colours on the ridge trail',
        article: 'A few shots from last weekend. The larches were at their peak.\n\nIf you go, start early. By midday the popular viewpoints get crowded.',
        created_at: ago(60),
        upvotes: 18,
        downvotes: 1,
        comments_count: 3,
    },
    {
        id: uuid(10),
        group_id: g(5),
        author: { name: 'Nina Petrova' },
        title: 'Do you write tests for your hobby projects?',
        article: 'Honest answers only. I feel guilty every time I skip them.\n\nI tell myself I will add them once the design settles, but that day never comes. How do you decide what is worth testing in a side project?',
        created_at: ago(360),
        upvotes: 160,
        downvotes: 6,
        comments_count: 63,
    },
    {
        // No title on purpose: shows how a card looks when the title is optional.
        id: uuid(11),
        group_id: g(1),
        author: { name: 'Karim Haddad' },
        title: null,
        article: 'My queue worker keeps dying after a few hours with no error in the logs. Supervisor restarts it but jobs get stuck in between. Has anyone seen this before?',
        created_at: ago(30),
        upvotes: 9,
        downvotes: 0,
        comments_count: 5,
    },
    {
        id: uuid(12),
        group_id: g(6),
        author: { name: 'Eva Lindgren' },
        title: 'This month\'s pick: a quiet mystery',
        article: 'Vote for this month\'s book. I am proposing a short mystery set in a small coastal town, easy to finish in two weekends.',
        created_at: ago(700),
        upvotes: 22,
        downvotes: 0,
        comments_count: 8,
    },
];

export const score = (post) => post.upvotes - post.downvotes;

export const findPost = (id) => dummyPosts.find((p) => p.id === id);
export const postsForGroup = (groupId) => dummyPosts.filter((p) => p.group_id === groupId);

// Guests and the public feed never see posts from private groups.
export const publicPosts = () => dummyPosts.filter((p) => !findGroup(p.group_id)?.is_private);
