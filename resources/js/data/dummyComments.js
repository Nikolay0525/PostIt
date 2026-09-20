// Dummy comments shaped like the `comments` table (parent_id for replies, is_deleted for removed ones).
// The same thread is shown under every post until the backend exists.
const uuid = (n) => `c0000000-0000-4000-8000-${String(n).padStart(12, '0')}`;
const ago = (minutes) => new Date(Date.now() - minutes * 60_000).toISOString();

export const dummyComments = [
    { id: uuid(1), parent_id: null, author: { name: 'Marta Kowalska' }, text: 'Great write-up, thanks for sharing this.', created_at: ago(70), upvotes: 12, downvotes: 0, is_deleted: false },
    { id: uuid(2), parent_id: uuid(1), author: { name: 'Jan Nowak' }, text: 'Agreed, the part about scope creep was spot on.', created_at: ago(55), upvotes: 4, downvotes: 0, is_deleted: false },
    { id: uuid(3), parent_id: uuid(2), author: { name: 'Marta Kowalska' }, text: 'It gets me every time!', created_at: ago(40), upvotes: 2, downvotes: 0, is_deleted: false },
    { id: uuid(4), parent_id: null, author: { name: 'أحمد الفارسي' }, text: 'شكراً لك، هذه معلومات مفيدة جداً.', created_at: ago(65), upvotes: 8, downvotes: 1, is_deleted: false },
    { id: uuid(5), parent_id: null, author: { name: 'Removed' }, text: '', created_at: ago(300), upvotes: 0, downvotes: 0, is_deleted: true },
    { id: uuid(6), parent_id: uuid(5), author: { name: 'דנה כהן' }, text: 'תודה על השיתוף, זה עזר לי מאוד.', created_at: ago(20), upvotes: 2, downvotes: 0, is_deleted: false },
];

// Turns the flat list into a tree: each comment gets a `replies` array.
export function buildCommentTree(comments) {
    const nodes = new Map(comments.map((c) => [c.id, { ...c, replies: [] }]));
    const roots = [];

    for (const node of nodes.values()) {
        const parent = node.parent_id && nodes.get(node.parent_id);
        (parent ? parent.replies : roots).push(node);
    }

    return roots;
}
