import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { deleteJson, postJson } from '@/utils/http';

// Subscribing to the group from its page or from one of its posts. `props` is the page's props:
// `group` and `is_member`.
//
// Requesting to join a private group is still a local toggle — GroupPolicy::subscribe()
// deliberately rejects private groups, since membership there only comes from an approved
// GroupJoinRequest, which doesn't exist yet. Subscribing to a public group is real.
export function useGroupMembership(props) {
    const page = usePage();

    const joined = ref(props.is_member);
    const showLoginPrompt = ref(false);
    const subscribing = ref(false);
    const subscribeError = ref(null);

    const buttonLabel = computed(() => {
        if (props.group.is_private) return trans(joined.value ? 'groups.show.request_sent' : 'groups.show.request');
        return trans(joined.value ? 'groups.show.subscribed' : 'groups.show.subscribe');
    });

    const toggleJoin = async () => {
        if (!page.props.auth.user) {
            showLoginPrompt.value = true;
            return;
        }

        if (props.group.is_private) {
            joined.value = !joined.value;
            return;
        }

        if (subscribing.value) return;

        subscribing.value = true;
        subscribeError.value = null;

        try {
            if (joined.value) {
                await deleteJson(`/groups/${props.group.id}/subscribe`);
            } else {
                await postJson(`/groups/${props.group.id}/subscribe`);
            }
            joined.value = !joined.value;
        } catch (error) {
            console.error('Subscribe failed:', error);
            subscribeError.value = error.message || trans('common.something_went_wrong');
        } finally {
            subscribing.value = false;
        }
    };

    return { joined, showLoginPrompt, subscribing, subscribeError, buttonLabel, toggleJoin };
}
