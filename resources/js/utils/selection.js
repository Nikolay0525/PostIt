// Where the reader's selection lies inside `container`, as positions in its text (the same units
// String.prototype.slice counts in) and the selected words: what a moderator marks in the content
// they judge. Null when nothing is selected there. Spaces at either end of the selection are left out.
export function selectionIn(container) {
    const selection = window.getSelection();

    if (!container || !selection || selection.rangeCount === 0 || selection.isCollapsed) return null;

    const range = selection.getRangeAt(0);

    if (!container.contains(range.startContainer) || !container.contains(range.endContainer)) return null;

    // Everything from the container's start up to the selection: its length is where the selection starts.
    const before = document.createRange();
    before.selectNodeContents(container);
    before.setEnd(range.startContainer, range.startOffset);

    const raw = range.toString();
    const quote = raw.trim();

    if (!quote) return null;

    const start = before.toString().length + (raw.length - raw.trimStart().length);

    return { start, end: start + quote.length, quote };
}
