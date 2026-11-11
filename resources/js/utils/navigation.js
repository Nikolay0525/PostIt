// Whether the user has moved through the app since this page was opened, so that going "back" lands
// on a page of ours. History entries only ever pile up while browsing (going back does not remove
// them), and this file is read when the app starts, before the first visit: if the history is longer
// than it was then, there is an entry of ours behind this page. A page opened by its address, or
// after a reload, has none, and a back button there must not throw the user out of the site.
const entriesAtStart = window.history.length;

export const hasPreviousPage = () => window.history.length > entriesAtStart;
