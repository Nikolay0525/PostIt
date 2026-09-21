// Dummy data shaped like the `groups` table. `members_count` will come from withCount('members').
const uuid = (n) => `a0000000-0000-4000-8000-${String(n).padStart(12, '0')}`;

export const dummyGroups = [
    {
        id: uuid(1),
        name: 'Laravel Developers',
        description: 'Tips, questions and showcases for people building with Laravel.',
        rules: 'Be kind. No spam. Put the Laravel version in questions.',
        icon_url: null,
        is_private: false,
        members_count: 12840,
    },
    {
        id: uuid(2),
        name: 'مبرمجون عرب',
        description: 'مجتمع للمبرمجين العرب لتبادل الخبرات والأسئلة والمشاريع.',
        rules: 'الاحترام المتبادل. ممنوع الإعلانات.',
        icon_url: null,
        is_private: false,
        members_count: 5310,
    },
    {
        id: uuid(3),
        name: 'Hiking & Trails',
        description: 'Routes, photos and gear advice from people who love the outdoors.',
        rules: 'Share your route details. Leave no trace.',
        icon_url: null,
        is_private: false,
        members_count: 9204,
    },
    {
        id: uuid(4),
        name: 'מפתחי ווב',
        description: 'קהילה למפתחי ווב לשיתוף ידע, שאלות והמלצות.',
        rules: 'כבדו זה את זה. אסור ספאם.',
        icon_url: null,
        is_private: false,
        members_count: 2175,
    },
    {
        id: uuid(5),
        name: 'Programming Chat',
        description: 'Casual conversation about code, tools and developer life.',
        rules: 'Keep it friendly and on topic.',
        icon_url: null,
        is_private: false,
        members_count: 20563,
    },
    {
        id: uuid(6),
        name: 'Weekend Book Club',
        description: 'A small private group discussing one book every month.',
        rules: 'Members only. No spoilers without a warning.',
        icon_url: null,
        is_private: true,
        members_count: 48,
    },
];

export const findGroup = (id) => dummyGroups.find((g) => g.id === id);
