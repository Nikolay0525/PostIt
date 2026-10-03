<script setup>
import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import PostFeed from '@/Pages/Components/PostFeed.vue';
import { formatCount } from '@/utils/format';
import { deleteJson, patchJson, postForm, postJson } from '@/utils/http';

const props = defineProps({
    profile: Object,
    is_self: Boolean,
    is_following: Boolean,
    achievements: Array,
    // Public groups, plus private ones the viewer is also in.
    groups: Array,
    // Paginated by <InfiniteScroll>; private-group posts only for members of that group.
    posts: Object,
    // Limits and emoji choices for the owner's edit form; null for everyone else.
    edit: Object,
});

const page = usePage();

// Every action below is a JSON call, not an Inertia visit: a visit would reset the post list
// already scrolled past page one. So the page keeps its own copy of what those calls change.
const info = reactive({
    avatar_url: props.profile.avatar_url,
    status_emoji: props.profile.status_emoji,
    status_text: props.profile.status_text,
    bio: props.profile.bio,
});

const failure = (e) => {
    console.error(e);
    return e.message || trans('common.something_went_wrong');
};

const joinedOn = new Date(props.profile.joined_at).toLocaleDateString(document.documentElement.lang || 'en', {
    year: 'numeric',
    month: 'long',
});

// --- Following ------------------------------------------------------------------------------

const following = ref(props.is_following);
const followersCount = ref(props.profile.followers_count);
const followBusy = ref(false);
const followError = ref(null);
const showLoginPrompt = ref(false);

const toggleFollow = async () => {
    if (!page.props.auth.user) {
        showLoginPrompt.value = true;
        return;
    }

    if (followBusy.value) return;

    followBusy.value = true;
    followError.value = null;

    try {
        const url = `/users/${props.profile.id}/follow`;
        const state = following.value ? await deleteJson(url) : await postJson(url);

        following.value = state.is_following;
        followersCount.value = state.followers_count;
    } catch (e) {
        followError.value = failure(e);
    } finally {
        followBusy.value = false;
    }
};

// --- Avatar (owner only) --------------------------------------------------------------------

const avatarInput = ref(null);
const avatarBusy = ref(false);
const avatarError = ref(null);

const uploadAvatar = async (event) => {
    const file = event.target.files[0];
    event.target.value = '';

    if (!file) return;

    // Checked on the server too; this only saves uploading a file that would be refused anyway.
    if (file.size > props.edit.avatar_max_kb * 1024) {
        avatarError.value = trans('users.edit.avatar_too_big', { mb: props.edit.avatar_max_kb / 1024 });
        return;
    }

    avatarBusy.value = true;
    avatarError.value = null;

    try {
        const form = new FormData();
        form.append('avatar', file);
        info.avatar_url = (await postForm('/profile/avatar', form)).avatar_url;
    } catch (e) {
        avatarError.value = failure(e);
    } finally {
        avatarBusy.value = false;
    }
};

const removeAvatar = async () => {
    avatarBusy.value = true;
    avatarError.value = null;

    try {
        info.avatar_url = (await deleteJson('/profile/avatar')).avatar_url;
    } catch (e) {
        avatarError.value = failure(e);
    } finally {
        avatarBusy.value = false;
    }
};

// --- Status and bio (owner only) ------------------------------------------------------------

const editing = ref(false);
const saving = ref(false);
const saveError = ref(null);
const draft = reactive({ status_emoji: null, status_text: '', bio: '' });

const startEditing = () => {
    draft.status_emoji = info.status_emoji;
    draft.status_text = info.status_text ?? '';
    draft.bio = info.bio ?? '';
    saveError.value = null;
    editing.value = true;
};

const saveProfile = async () => {
    saving.value = true;
    saveError.value = null;

    try {
        Object.assign(info, await patchJson('/profile', { ...draft }));
        editing.value = false;
    } catch (e) {
        saveError.value = failure(e);
    } finally {
        saving.value = false;
    }
};

const hasStatus = computed(() => info.status_emoji || info.status_text);
</script>

<template>
    <Head :title="` | ${profile.username}`" />

    <section class="feed">
        <!-- Who this is: picture, name, status, numbers and the main action, all in one card. -->
        <div class="profile-card">
            <div class="profile-header">
                <!-- For the owner the picture itself is the upload button. -->
                <button
                    v-if="is_self"
                    type="button"
                    class="profile-avatar profile-avatar-btn group"
                    :disabled="avatarBusy"
                    :aria-label="$t('users.edit.change_photo')"
                    :title="$t('users.edit.change_photo')"
                    @click="avatarInput.click()"
                >
                    <img v-if="info.avatar_url" :src="info.avatar_url" alt="" class="h-full w-full object-cover" />
                    <span v-else aria-hidden="true">{{ profile.username.charAt(0).toUpperCase() }}</span>

                    <span class="profile-avatar-overlay" :class="{ 'is-busy': avatarBusy }" aria-hidden="true">
                        <span v-if="avatarBusy" class="text-2xl">…</span>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                    </span>
                </button>
                <span v-else class="profile-avatar" aria-hidden="true">
                    <img v-if="info.avatar_url" :src="info.avatar_url" alt="" class="h-full w-full object-cover" />
                    <template v-else>{{ profile.username.charAt(0).toUpperCase() }}</template>
                </span>
                <input v-if="is_self" ref="avatarInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="uploadAvatar" />

                <div class="min-w-0 flex-1">
                    <h1 class="group-name profile-text" dir="auto">{{ profile.username }}</h1>
                    <p v-if="hasStatus" class="profile-status profile-text" dir="auto">
                        <span v-if="info.status_emoji" aria-hidden="true">{{ info.status_emoji }}</span>
                        {{ info.status_text }}
                    </p>
                    <p class="group-stats mt-1">
                        {{ $tChoice('users.show.followers', followersCount, { num: formatCount(followersCount) }) }}
                        · {{ $t('users.show.joined', { date: joinedOn }) }}
                    </p>
                </div>

                <button v-if="is_self" v-show="!editing" type="button" class="btn-secondary" @click="startEditing">
                    {{ $t('users.edit.edit') }}
                </button>
                <button
                    v-else
                    type="button"
                    :class="following ? 'btn-secondary' : 'btn-primary'"
                    :disabled="followBusy"
                    @click="toggleFollow"
                >{{ $t(following ? 'users.show.following' : 'users.show.follow') }}</button>
            </div>

            <p v-if="avatarError" class="vote-error" :title="avatarError">⚠ {{ avatarError }}</p>
            <p v-if="followError" class="vote-error" :title="followError">⚠ {{ followError }}</p>
            <p v-if="showLoginPrompt" class="post-login-prompt">
                <AuthPrompt action="follow" />
            </p>
            <p v-if="is_self && !editing && !hasStatus && !info.bio" class="text-sm text-muted">{{ $t('users.edit.empty_hint') }}</p>

            <form v-if="editing" class="profile-edit-form" @submit.prevent="saveProfile">
                <div>
                    <p class="field-label">{{ $t('users.edit.status') }}</p>
                    <div class="emoji-picker" role="radiogroup" :aria-label="$t('users.edit.status_emoji')">
                        <button
                            type="button"
                            class="emoji-option"
                            :class="{ 'is-selected': !draft.status_emoji }"
                            :aria-checked="!draft.status_emoji"
                            role="radio"
                            :title="$t('users.edit.no_emoji')"
                            @click="draft.status_emoji = null"
                        >∅</button>
                        <button
                            v-for="emoji in edit.status_emojis"
                            :key="emoji"
                            type="button"
                            class="emoji-option"
                            :class="{ 'is-selected': draft.status_emoji === emoji }"
                            :aria-checked="draft.status_emoji === emoji"
                            role="radio"
                            @click="draft.status_emoji = emoji"
                        >{{ emoji }}</button>
                    </div>
                    <input
                        v-model="draft.status_text"
                        type="text"
                        class="field-input mt-2"
                        :maxlength="edit.status_text_max"
                        :placeholder="$t('users.edit.status_placeholder')"
                        dir="auto"
                    />
                    <p class="field-hint">{{ draft.status_text.length }} / {{ edit.status_text_max }}</p>
                </div>

                <div>
                    <label class="field-label" for="profile-bio">{{ $t('users.edit.bio') }}</label>
                    <textarea
                        id="profile-bio"
                        v-model="draft.bio"
                        class="field-input profile-textarea"
                        rows="4"
                        :maxlength="edit.bio_max"
                        :placeholder="$t('users.edit.bio_placeholder')"
                        dir="auto"
                    ></textarea>
                    <p class="field-hint">{{ draft.bio.length }} / {{ edit.bio_max }}</p>
                </div>

                <p v-if="saveError" class="vote-error" :title="saveError">⚠ {{ saveError }}</p>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? $t('users.edit.saving') : $t('users.edit.save') }}
                    </button>
                    <button type="button" class="btn-secondary" :disabled="saving" @click="editing = false">
                        {{ $t('users.edit.cancel') }}
                    </button>
                    <!-- Removing the photo lives here; adding one is a click on the picture itself. -->
                    <button v-if="info.avatar_url" type="button" class="profile-link-btn ms-auto" :disabled="avatarBusy" @click="removeAvatar">
                        {{ $t('users.edit.remove_photo') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Only when the person wrote something (heading included); while editing, the text is in
             the form above. -->
        <template v-if="info.bio && !editing">
            <h2 class="feed-title" dir="auto">{{ $t('users.edit.bio') }}</h2>

            <div class="profile-card">
                <p class="profile-bio profile-text" dir="auto">{{ info.bio }}</p>
            </div>
        </template>

        <h2 class="feed-title" dir="auto">{{ $t('users.show.achievements') }}</h2>

        <!-- Nothing awards achievements yet, so for now this mostly shows the empty line. -->
        <div class="profile-card">
            <ul v-if="achievements.length" class="achievement-list">
                <li v-for="achievement in achievements" :key="achievement.id" class="achievement" :title="achievement.description">
                    <img v-if="achievement.icon_url" :src="achievement.icon_url" alt="" class="h-10 w-10" />
                    <span v-else class="text-3xl" aria-hidden="true">🏅</span>
                    <span class="text-xs text-ink" dir="auto">{{ achievement.title }}</span>
                </li>
            </ul>
            <p v-else class="text-sm text-muted">{{ $t('users.show.no_achievements') }}</p>
        </div>

        <h2 class="feed-title" dir="auto">{{ $t('users.show.groups') }}</h2>

        <div class="profile-card">
            <div v-if="groups.length" class="language-chips">
                <Link v-for="group in groups" :key="group.id" :href="route('groups.show', group.slug)" class="language-chip pe-3" dir="auto">
                    <span v-if="group.is_private" :title="$t('groups.show.private')" aria-hidden="true">🔒</span>
                    {{ group.name }}
                </Link>
            </div>
            <p v-else class="text-sm text-muted">{{ $t('users.show.no_groups') }}</p>
        </div>

        <PostFeed :title="$t('users.show.posts')" :posts="posts" />
    </section>
</template>
