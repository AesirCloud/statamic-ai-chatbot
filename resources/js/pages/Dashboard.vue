<script setup>
import { Head } from '@statamic/cms/inertia';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    profiles: {
        type: Array,
        default: () => [],
    },
    faqs: {
        type: Array,
        default: () => [],
    },
    sources: {
        type: Array,
        default: () => [],
    },
    conversations: {
        type: Array,
        default: () => [],
    },
    leads: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    drivers: {
        type: Array,
        default: () => [],
    },
    providerCatalog: {
        type: Array,
        default: () => [],
    },
    providerOptions: {
        type: Array,
        default: () => [],
    },
    sites: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
    options: {
        type: Object,
        default: () => ({}),
    },
    routes: {
        type: Object,
        default: () => ({}),
    },
});

const dashboard = ref(buildDashboard(props));
const busy = reactive({
    sync: false,
    settings: false,
    profile: false,
    faq: false,
    source: false,
    sourceSync: false,
    lead: false,
    conversation: false,
});

const selectedSyncProfile = ref(dashboard.value.settings.default_profile_handle || '');
const selectedProfileId = ref(dashboard.value.profiles[0]?.id ?? null);
const selectedFaqId = ref(dashboard.value.faqs[0]?.id ?? null);
const selectedSourceId = ref(dashboard.value.sources[0]?.id ?? null);
const selectedConversationId = ref(dashboard.value.conversations[0]?.id ?? null);
const selectedLeadId = ref(dashboard.value.leads[0]?.id ?? null);

const settingsForm = ref(normalizeSettings(dashboard.value.settings));
const profileForm = ref(defaultProfileForm(dashboard.value.profiles[0]?.id ?? null));
const faqForm = ref(defaultFaqForm(dashboard.value.profiles[0]?.id ?? null));
const sourceForm = ref(defaultSourceForm(dashboard.value.profiles[0]?.id ?? null));
const leadForm = ref(defaultLeadForm(dashboard.value.profiles[0]?.id ?? null));

const hasProfiles = computed(() => dashboard.value.profiles.length > 0);
const retentionModes = computed(() => dashboard.value.options.retentionModes ?? []);
const widgetPositions = computed(() => dashboard.value.options.widgetPositions ?? []);
const leadStatuses = computed(() => dashboard.value.options.leadStatuses ?? []);
const profileOptions = computed(() => dashboard.value.profiles.map((profile) => ({
    value: profile.id,
    label: `${profile.name} (${profile.handle})`,
})));

const providerSummary = computed(() => {
    const settings = settingsForm.value;

    return {
        text: [settings.providers.text.driver, settings.providers.text.model].filter(Boolean).join(' / '),
        embeddings: settings.providers.embeddings.enabled
            ? [settings.providers.embeddings.driver, settings.providers.embeddings.model].filter(Boolean).join(' / ')
            : 'Disabled',
    };
});

function csrfToken() {
    const fromStatamicConfig = window.Statamic?.$config?.get?.('csrfToken');

    if (fromStatamicConfig) {
        return fromStatamicConfig;
    }

    const fromMeta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (fromMeta) {
        return fromMeta;
    }

    const xsrfCookie = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.split('=')
        .slice(1)
        .join('=');

    if (!xsrfCookie) {
        return '';
    }

    try {
        return decodeURIComponent(xsrfCookie);
    } catch (error) {
        return xsrfCookie;
    }
}

function requestHeaders() {
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (csrfToken()) {
        headers['X-CSRF-TOKEN'] = csrfToken();
    }

    return headers;
}

async function parseDashboardResponse(response) {
    const contentType = response.headers.get('content-type') ?? '';
    const isJson = contentType.includes('application/json');
    const payload = isJson ? await response.json() : null;

    if (!response.ok) {
        const message = payload?.message
            ?? (response.status === 419
                ? 'Your control panel session expired. Refresh the page and try again.'
                : response.status === 401 || response.status === 403
                    ? 'This action is not authorized for the current session.'
                    : `Request failed with status ${response.status}.`);

        const error = new Error(message);

        error.response = {
            status: response.status,
            data: payload ?? {},
        };

        throw error;
    }

    if (!payload || typeof payload !== 'object') {
        throw new Error('The control panel returned an unexpected response. Refresh the page and try again.');
    }

    return payload;
}

const selectedConversation = computed(() => (
    dashboard.value.conversations.find((conversation) => conversation.id === selectedConversationId.value) ?? null
));

watch(selectedProfileId, (id) => {
    if (!id) {
        return;
    }

    loadProfileForm(id);
});

watch(selectedFaqId, (id) => {
    if (!id) {
        return;
    }

    loadFaqForm(id);
});

watch(selectedSourceId, (id) => {
    if (!id) {
        return;
    }

    loadSourceForm(id);
});

watch(selectedLeadId, (id) => {
    if (!id) {
        return;
    }

    loadLeadForm(id);
});

watch(() => sourceForm.value.driver, (driver) => {
    if (driver === 'youtube' && sourceForm.value.youtube_items.length === 0) {
        addYouTubeItem();
    }
});

if (selectedProfileId.value) {
    loadProfileForm(selectedProfileId.value);
}

if (selectedFaqId.value) {
    loadFaqForm(selectedFaqId.value);
}

if (selectedSourceId.value) {
    loadSourceForm(selectedSourceId.value);
}

if (selectedLeadId.value) {
    loadLeadForm(selectedLeadId.value);
}

function buildDashboard(source) {
    return {
        profiles: clone(source.profiles ?? []),
        faqs: clone(source.faqs ?? []),
        sources: clone(source.sources ?? []),
        conversations: clone(source.conversations ?? []),
        leads: clone(source.leads ?? []),
        stats: clone(source.stats ?? {}),
        drivers: clone(source.drivers ?? []),
        providerCatalog: clone(source.providerCatalog ?? []),
        providerOptions: clone(source.providerOptions ?? []),
        sites: clone(source.sites ?? []),
        settings: normalizeSettings(source.settings ?? {}),
        options: clone(source.options ?? {}),
        routes: clone(source.routes ?? {}),
    };
}

function clone(value) {
    return JSON.parse(JSON.stringify(value ?? null));
}

function normalizeSettings(settings) {
    const next = clone(settings ?? {});

    next.enabled ??= true;
    next.providers ??= {};
    next.providers.text ??= { driver: 'openai', model: 'gpt-5-mini' };
    next.providers.embeddings ??= { driver: 'openai', model: 'text-embedding-3-small', dimensions: 1536, enabled: true };
    next.providers.reranking ??= { driver: '', model: '', enabled: false };
    next.retention ??= { mode: 'conversations_and_leads', conversation_days: 90, lead_days: 365 };
    next.queue ??= { connection: '', queue: 'default' };
    next.widget ??= {
        position: 'bottom-right',
        width: '26rem',
        eyebrow_label: 'AesirCloud AI',
        launcher_label: 'Chat with us',
        welcome_title: 'How can we help?',
        welcome_message: 'Ask a question, browse FAQs, or reach support.',
        primary_color: '#0f766e',
        accent_color: '#f4a261',
        button_text_color: '#14362f',
        surface_color: '#f1f4f5',
        surface_text_color: '#5b6670',
        border_color: '#d9e0e3',
        support_hours: '',
        privacy_notice: '',
        logo_url: '',
    };
    next.knowledge ??= {
        max_chunks: 6,
        max_chunk_characters: 1200,
        chunk_overlap_characters: 150,
        min_similarity: 0.28,
        rerank_top_n: 5,
    };
    next.lead_destinations ??= {
        database: true,
        email: { enabled: false, to: '' },
        webhook: { enabled: false, url: '', secret: '' },
    };
    next.lead_destinations.email ??= { enabled: false, to: '' };
    next.lead_destinations.webhook ??= { enabled: false, url: '', secret: '' };
    next.youtube ??= { enabled: true, timeout: 15 };
    next.ai ??= {
        default: 'openai',
        default_for_embeddings: 'openai',
        default_for_reranking: 'cohere',
    };

    return next;
}

function defaultProfileForm(profileId = null) {
    return {
        id: profileId,
        handle: '',
        name: '',
        site: '',
        locale: '',
        is_default: false,
        active: true,
        branding: {
            voice: '',
        },
        provider_overrides: {
            text: {
                driver: '',
                model: '',
            },
            embeddings: {
                driver: '',
                model: '',
                dimensions: '',
                enabled: false,
            },
        },
        widget_settings: {
            position: '',
            width: '',
            eyebrow_label: '',
            launcher_label: '',
            welcome_title: '',
            welcome_message: '',
            primary_color: '',
            accent_color: '',
            button_text_color: '',
            surface_color: '',
            surface_text_color: '',
            border_color: '',
            support_hours: '',
            privacy_notice: '',
            logo_url: '',
        },
        support_settings: {
            contact_url: '',
            email: '',
            phone: '',
            label: '',
        },
        lead_settings: {
            enabled: true,
            headline: '',
            description: '',
        },
        system_prompt: '',
    };
}

function defaultFaqForm(profileId = null) {
    return {
        id: null,
        bot_profile_id: profileId,
        site: '',
        locale: '',
        question: '',
        question_variants_text: '',
        answer: '',
        priority: 0,
        active: true,
        lead_capture_fields_text: 'name, email, message',
        cta_actions: [defaultCtaAction()],
    };
}

function defaultSourceForm(profileId = null) {
    return {
        id: null,
        bot_profile_id: profileId,
        driver: 'statamic',
        name: '',
        active: true,
        statamic: {
            sites_text: '',
            collections_text: '',
            globals_text: '',
            navs_text: '',
            taxonomies_text: '',
        },
        youtube_items: [defaultYouTubeItem()],
    };
}

function defaultLeadForm(profileId = null) {
    return {
        id: null,
        bot_profile_id: profileId,
        chat_conversation_id: '',
        site: '',
        locale: '',
        name: '',
        email: '',
        phone: '',
        message: '',
        status: 'new',
    };
}

function defaultCtaAction() {
    return {
        type: 'link',
        label: '',
        url: '',
        value: '',
    };
}

function defaultYouTubeItem() {
    return {
        url: '',
        timestamp: '',
        transcript: '',
    };
}

function splitDelimited(value) {
    return String(value ?? '')
        .split(/\n|,/)
        .map((item) => item.trim())
        .filter(Boolean);
}

function handleError(error) {
    const validationErrors = error?.response?.data?.errors;
    const firstValidation = validationErrors
        ? Object.values(validationErrors).flat()[0]
        : null;

    const status = error?.response?.status;
    const fallbackMessage = status === 419
        ? 'Your control panel session expired. Refresh the page and try again.'
        : status === 401 || status === 403
            ? 'This action is not authorized for the current session.'
            : 'Something went wrong.';

    window.Statamic.$toast.error(
        firstValidation
        ?? error?.response?.data?.message
        ?? error?.message
        ?? fallbackMessage
    );
}

async function requestDashboard(key, url, payload) {
    busy[key] = true;

    try {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: requestHeaders(),
            body: JSON.stringify(payload),
        });
        const data = await parseDashboardResponse(response);

        if (!data || typeof data !== 'object' || !('data' in data)) {
            throw new Error('The control panel returned an unexpected response. Refresh the page and try again.');
        }

        hydrate(data.data);
        window.Statamic.$toast.success(data.message ?? 'Saved.');

        return data;
    } catch (error) {
        handleError(error);
        throw error;
    } finally {
        busy[key] = false;
    }
}

function hydrate(nextData) {
    const previousSelections = {
        profile: selectedProfileId.value,
        faq: selectedFaqId.value,
        source: selectedSourceId.value,
        conversation: selectedConversationId.value,
        lead: selectedLeadId.value,
    };

    dashboard.value = buildDashboard(nextData);
    settingsForm.value = normalizeSettings(dashboard.value.settings);
    selectedSyncProfile.value = dashboard.value.settings.default_profile_handle || '';

    selectedProfileId.value = dashboard.value.profiles.some((profile) => profile.id === previousSelections.profile)
        ? previousSelections.profile
        : dashboard.value.profiles[0]?.id ?? null;
    selectedFaqId.value = dashboard.value.faqs.some((faq) => faq.id === previousSelections.faq)
        ? previousSelections.faq
        : dashboard.value.faqs[0]?.id ?? null;
    selectedSourceId.value = dashboard.value.sources.some((source) => source.id === previousSelections.source)
        ? previousSelections.source
        : dashboard.value.sources[0]?.id ?? null;
    selectedConversationId.value = dashboard.value.conversations.some((conversation) => conversation.id === previousSelections.conversation)
        ? previousSelections.conversation
        : dashboard.value.conversations[0]?.id ?? null;
    selectedLeadId.value = dashboard.value.leads.some((lead) => lead.id === previousSelections.lead)
        ? previousSelections.lead
        : dashboard.value.leads[0]?.id ?? null;

    if (selectedProfileId.value) {
        loadProfileForm(selectedProfileId.value);
    } else {
        profileForm.value = defaultProfileForm();
    }

    if (selectedFaqId.value) {
        loadFaqForm(selectedFaqId.value);
    } else {
        faqForm.value = defaultFaqForm(dashboard.value.profiles[0]?.id ?? null);
    }

    if (selectedSourceId.value) {
        loadSourceForm(selectedSourceId.value);
    } else {
        sourceForm.value = defaultSourceForm(dashboard.value.profiles[0]?.id ?? null);
    }

    if (selectedLeadId.value) {
        loadLeadForm(selectedLeadId.value);
    } else {
        leadForm.value = defaultLeadForm(dashboard.value.profiles[0]?.id ?? null);
    }
}

function startNewProfile() {
    selectedProfileId.value = null;
    profileForm.value = defaultProfileForm();
}

function loadProfileForm(id) {
    const profile = dashboard.value.profiles.find((item) => item.id === id);

    if (!profile) {
        return;
    }

    profileForm.value = {
        ...defaultProfileForm(id),
        ...clone(profile),
        branding: {
            ...defaultProfileForm().branding,
            ...(profile.branding ?? {}),
        },
        provider_overrides: {
            ...defaultProfileForm().provider_overrides,
            ...(profile.provider_overrides ?? {}),
            text: {
                ...defaultProfileForm().provider_overrides.text,
                ...(profile.provider_overrides?.text ?? {}),
            },
            embeddings: {
                ...defaultProfileForm().provider_overrides.embeddings,
                ...(profile.provider_overrides?.embeddings ?? {}),
            },
        },
        widget_settings: {
            ...defaultProfileForm().widget_settings,
            ...(profile.widget_settings ?? {}),
        },
        support_settings: {
            ...defaultProfileForm().support_settings,
            ...(profile.support_settings ?? {}),
        },
        lead_settings: {
            ...defaultProfileForm().lead_settings,
            ...(profile.lead_settings ?? {}),
        },
    };
}

async function saveProfile() {
    const wasNew = !profileForm.value.id;
    const handle = profileForm.value.handle;
    await requestDashboard('profile', dashboard.value.routes.profileSave, profileForm.value);

    if (wasNew) {
        const created = dashboard.value.profiles.find((profile) => profile.handle === handle);

        if (created) {
            selectedProfileId.value = created.id;
            loadProfileForm(created.id);
        }
    }
}

async function deleteProfile(id = profileForm.value.id) {
    if (!id || !window.confirm('Delete this profile and all of its FAQs, sources, chats, and leads?')) {
        return;
    }

    await requestDashboard('profile', dashboard.value.routes.profileDelete, { id });
}

function startNewFaq() {
    selectedFaqId.value = null;
    faqForm.value = defaultFaqForm(dashboard.value.profiles[0]?.id ?? null);
}

function loadFaqForm(id) {
    const faq = dashboard.value.faqs.find((item) => item.id === id);

    if (!faq) {
        return;
    }

    faqForm.value = {
        id: faq.id,
        bot_profile_id: faq.bot_profile_id,
        site: faq.site ?? '',
        locale: faq.locale ?? '',
        question: faq.question,
        question_variants_text: (faq.question_variants ?? []).join(', '),
        answer: faq.answer,
        priority: faq.priority ?? 0,
        active: faq.active,
        lead_capture_fields_text: (faq.lead_capture_fields ?? []).join(', '),
        cta_actions: clone(faq.cta_actions?.length ? faq.cta_actions : [defaultCtaAction()]),
    };
}

function addCtaAction() {
    faqForm.value.cta_actions.push(defaultCtaAction());
}

function removeCtaAction(index) {
    faqForm.value.cta_actions.splice(index, 1);

    if (faqForm.value.cta_actions.length === 0) {
        addCtaAction();
    }
}

async function saveFaq() {
    const payload = {
        id: faqForm.value.id,
        bot_profile_id: faqForm.value.bot_profile_id,
        site: faqForm.value.site || null,
        locale: faqForm.value.locale || null,
        question: faqForm.value.question,
        question_variants: splitDelimited(faqForm.value.question_variants_text),
        answer: faqForm.value.answer,
        priority: Number(faqForm.value.priority || 0),
        active: faqForm.value.active,
        lead_capture_fields: splitDelimited(faqForm.value.lead_capture_fields_text),
        cta_actions: faqForm.value.cta_actions,
    };
    const wasNew = !payload.id;
    const question = payload.question;

    await requestDashboard('faq', dashboard.value.routes.faqSave, payload);

    if (wasNew) {
        const created = dashboard.value.faqs.find((faq) => faq.question === question);

        if (created) {
            selectedFaqId.value = created.id;
            loadFaqForm(created.id);
        }
    }
}

async function deleteFaq(id = faqForm.value.id) {
    if (!id || !window.confirm('Delete this FAQ?')) {
        return;
    }

    await requestDashboard('faq', dashboard.value.routes.faqDelete, { id });
}

function startNewSource() {
    selectedSourceId.value = null;
    sourceForm.value = defaultSourceForm(dashboard.value.profiles[0]?.id ?? null);
}

function loadSourceForm(id) {
    const source = dashboard.value.sources.find((item) => item.id === id);

    if (!source) {
        return;
    }

    sourceForm.value = {
        id: source.id,
        bot_profile_id: source.bot_profile_id,
        driver: source.driver,
        name: source.name,
        active: source.active,
        statamic: {
            sites_text: (source.config?.sites ?? []).join(', '),
            collections_text: (source.config?.collections ?? []).join(', '),
            globals_text: (source.config?.globals ?? []).join(', '),
            navs_text: (source.config?.navs ?? []).join(', '),
            taxonomies_text: (source.config?.taxonomies ?? []).join(', '),
        },
        youtube_items: clone(source.config?.items?.length ? source.config.items : [defaultYouTubeItem()]),
    };
}

function addYouTubeItem() {
    sourceForm.value.youtube_items.push(defaultYouTubeItem());
}

function removeYouTubeItem(index) {
    sourceForm.value.youtube_items.splice(index, 1);

    if (sourceForm.value.youtube_items.length === 0) {
        addYouTubeItem();
    }
}

async function saveSource() {
    const payload = {
        id: sourceForm.value.id,
        bot_profile_id: sourceForm.value.bot_profile_id,
        driver: sourceForm.value.driver,
        name: sourceForm.value.name,
        active: sourceForm.value.active,
        config: sourceForm.value.driver === 'youtube'
            ? {
                items: sourceForm.value.youtube_items,
            }
            : {
                sites: splitDelimited(sourceForm.value.statamic.sites_text),
                collections: splitDelimited(sourceForm.value.statamic.collections_text),
                globals: splitDelimited(sourceForm.value.statamic.globals_text),
                navs: splitDelimited(sourceForm.value.statamic.navs_text),
                taxonomies: splitDelimited(sourceForm.value.statamic.taxonomies_text),
            },
    };
    const wasNew = !payload.id;
    const matchName = payload.name;

    await requestDashboard('source', dashboard.value.routes.sourceSave, payload);

    if (wasNew) {
        const created = dashboard.value.sources.find((source) => source.name === matchName && source.driver === payload.driver);

        if (created) {
            selectedSourceId.value = created.id;
            loadSourceForm(created.id);
        }
    }
}

async function deleteSource(id = sourceForm.value.id) {
    if (!id || !window.confirm('Delete this source and its indexed knowledge documents?')) {
        return;
    }

    await requestDashboard('source', dashboard.value.routes.sourceDelete, { id });
}

async function syncSource(id) {
    if (!id) {
        return;
    }

    await requestDashboard('sourceSync', dashboard.value.routes.sourceSync, { id });
}

async function syncKnowledge() {
    await requestDashboard('sync', dashboard.value.routes.sync, {
        profile: selectedSyncProfile.value || null,
    });
}

function startNewLead() {
    selectedLeadId.value = null;
    leadForm.value = defaultLeadForm(dashboard.value.profiles[0]?.id ?? null);
}

function loadLeadForm(id) {
    const lead = dashboard.value.leads.find((item) => item.id === id);

    if (!lead) {
        return;
    }

    leadForm.value = {
        id: lead.id,
        bot_profile_id: lead.bot_profile_id,
        chat_conversation_id: lead.chat_conversation_id ?? '',
        site: lead.site ?? '',
        locale: lead.locale ?? '',
        name: lead.name,
        email: lead.email,
        phone: lead.phone ?? '',
        message: lead.message ?? '',
        status: lead.status ?? 'new',
    };
}

async function saveLead() {
    const payload = {
        ...leadForm.value,
        chat_conversation_id: leadForm.value.chat_conversation_id || null,
        site: leadForm.value.site || null,
        locale: leadForm.value.locale || null,
    };
    const wasNew = !payload.id;
    const matchLead = `${payload.email}:${payload.name}`;

    await requestDashboard('lead', dashboard.value.routes.leadSave, payload);

    if (wasNew) {
        const created = dashboard.value.leads.find((lead) => `${lead.email}:${lead.name}` === matchLead);

        if (created) {
            selectedLeadId.value = created.id;
            loadLeadForm(created.id);
        }
    }
}

async function deleteLead(id = leadForm.value.id) {
    if (!id || !window.confirm('Delete this lead record?')) {
        return;
    }

    await requestDashboard('lead', dashboard.value.routes.leadDelete, { id });
}

async function deleteConversation(id) {
    if (!id || !window.confirm('Delete this conversation and all stored messages?')) {
        return;
    }

    await requestDashboard('conversation', dashboard.value.routes.conversationDelete, { id });
}

async function saveSettings() {
    await requestDashboard('settings', dashboard.value.routes.settingsSave, settingsForm.value);
}
</script>

<template>
    <div class="aesircloud-ai-chatbot-cp max-w-page mx-auto">
        <Head title="AI Chatbot" />

        <section class="hero">
            <div>
                <p class="eyebrow">AesirCloud</p>
                <h1>Statamic AI Chatbot</h1>
                <p class="lede">
                    Configure defaults, profiles, FAQs, sources, chat review, and lead capture from
                    one place.
                </p>
            </div>

            <div class="hero-actions">
                <label class="field">
                    <span>Sync profile</span>
                    <select v-model="selectedSyncProfile">
                        <option value="">All profiles</option>
                        <option
                            v-for="profile in dashboard.profiles"
                            :key="profile.id"
                            :value="profile.handle"
                        >
                            {{ profile.name }} ({{ profile.handle }})
                        </option>
                    </select>
                </label>

                <button
                    class="sync-button"
                    :disabled="busy.sync"
                    @click="syncKnowledge"
                >
                    {{ busy.sync ? 'Syncing…' : 'Run knowledge sync' }}
                </button>
            </div>
        </section>

        <section class="stats-grid">
            <article class="stat-card">
                <span class="stat-label">Profiles</span>
                <strong>{{ dashboard.stats.profiles ?? 0 }}</strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">FAQs</span>
                <strong>{{ dashboard.stats.faqs ?? 0 }}</strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">Sources</span>
                <strong>{{ dashboard.stats.sources ?? 0 }}</strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">Conversations</span>
                <strong>{{ dashboard.stats.conversations ?? 0 }}</strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">Leads</span>
                <strong>{{ dashboard.stats.leads ?? 0 }}</strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">Text provider</span>
                <strong>{{ providerSummary.text }}</strong>
            </article>
        </section>

        <section class="panel">
            <header class="panel-header">
                <div>
                    <p class="panel-kicker">Runtime config</p>
                    <h2>Global settings</h2>
                </div>
                <button
                    class="action-button action-button--primary"
                    :disabled="busy.settings"
                    @click="saveSettings"
                >
                    {{ busy.settings ? 'Saving…' : 'Save settings' }}
                </button>
            </header>

            <div class="settings-grid">
                <div class="stack">
                    <section class="runtime-toggle">
                        <div class="runtime-toggle__content">
                            <p class="panel-kicker">Live status</p>
                            <h3>{{ settingsForm.enabled ? 'Chatbot is enabled' : 'Chatbot is disabled' }}</h3>
                            <p class="field-note">
                                Disable this to hide the widget on the site and reject public chat or lead submissions.
                                Click Save settings to apply the change.
                            </p>
                        </div>

                        <label class="runtime-toggle__switch">
                            <span>{{ settingsForm.enabled ? 'Enabled' : 'Disabled' }}</span>
                            <input v-model="settingsForm.enabled" type="checkbox">
                        </label>
                    </section>

                    <h3>Addon defaults</h3>

                    <div class="field-grid field-grid--3">
                        <label class="field">
                            <span>Default profile handle</span>
                            <input v-model="settingsForm.default_profile_handle" type="text">
                        </label>

                        <label class="field">
                            <span>Text provider</span>
                            <select v-model="settingsForm.providers.text.driver">
                                <option
                                    v-for="provider in dashboard.providerOptions"
                                    :key="provider.key"
                                    :value="provider.key"
                                >
                                    {{ provider.label }}
                                </option>
                            </select>
                        </label>

                        <label class="field">
                            <span>Text model</span>
                            <input v-model="settingsForm.providers.text.model" type="text">
                        </label>
                    </div>

                    <div class="field-grid field-grid--4">
                        <label class="field checkbox-field">
                            <span>Embeddings enabled</span>
                            <input v-model="settingsForm.providers.embeddings.enabled" type="checkbox">
                        </label>

                        <label class="field">
                            <span>Embeddings provider</span>
                            <select v-model="settingsForm.providers.embeddings.driver">
                                <option
                                    v-for="provider in dashboard.providerOptions"
                                    :key="provider.key"
                                    :value="provider.key"
                                >
                                    {{ provider.label }}
                                </option>
                            </select>
                        </label>

                        <label class="field">
                            <span>Embeddings model</span>
                            <input v-model="settingsForm.providers.embeddings.model" type="text">
                        </label>

                        <label class="field">
                            <span>Embedding dimensions</span>
                            <input v-model.number="settingsForm.providers.embeddings.dimensions" type="number" min="1">
                        </label>
                    </div>

                    <div class="field-grid field-grid--4">
                        <label class="field checkbox-field">
                            <span>Reranking enabled</span>
                            <input v-model="settingsForm.providers.reranking.enabled" type="checkbox">
                        </label>

                        <label class="field">
                            <span>Reranking provider</span>
                            <select v-model="settingsForm.providers.reranking.driver">
                                <option value="">None</option>
                                <option
                                    v-for="provider in dashboard.providerOptions"
                                    :key="provider.key"
                                    :value="provider.key"
                                >
                                    {{ provider.label }}
                                </option>
                            </select>
                        </label>

                        <label class="field">
                            <span>Reranking model</span>
                            <input v-model="settingsForm.providers.reranking.model" type="text">
                        </label>

                        <label class="field">
                            <span>SDK default</span>
                            <select v-model="settingsForm.ai.default">
                                <option
                                    v-for="provider in dashboard.providerOptions"
                                    :key="provider.key"
                                    :value="provider.key"
                                >
                                    {{ provider.label }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="field-grid field-grid--4">
                        <label class="field">
                            <span>Embeddings default</span>
                            <select v-model="settingsForm.ai.default_for_embeddings">
                                <option
                                    v-for="provider in dashboard.providerOptions"
                                    :key="provider.key"
                                    :value="provider.key"
                                >
                                    {{ provider.label }}
                                </option>
                            </select>
                        </label>

                        <label class="field">
                            <span>Reranking default</span>
                            <select v-model="settingsForm.ai.default_for_reranking">
                                <option value="">None</option>
                                <option
                                    v-for="provider in dashboard.providerOptions"
                                    :key="provider.key"
                                    :value="provider.key"
                                >
                                    {{ provider.label }}
                                </option>
                            </select>
                        </label>

                        <label class="field">
                            <span>Retention mode</span>
                            <select v-model="settingsForm.retention.mode">
                                <option
                                    v-for="mode in retentionModes"
                                    :key="mode.value"
                                    :value="mode.value"
                                >
                                    {{ mode.label }}
                                </option>
                            </select>
                        </label>

                        <label class="field">
                            <span>Queue name</span>
                            <input v-model="settingsForm.queue.queue" type="text">
                        </label>
                    </div>

                    <div class="field-grid field-grid--4">
                        <label class="field">
                            <span>Conversation retention days</span>
                            <input v-model.number="settingsForm.retention.conversation_days" type="number" min="1">
                        </label>

                        <label class="field">
                            <span>Lead retention days</span>
                            <input v-model.number="settingsForm.retention.lead_days" type="number" min="1">
                        </label>

                        <label class="field">
                            <span>Knowledge max chunks</span>
                            <input v-model.number="settingsForm.knowledge.max_chunks" type="number" min="1">
                        </label>

                        <label class="field">
                            <span>Rerank top N</span>
                            <input v-model.number="settingsForm.knowledge.rerank_top_n" type="number" min="1">
                        </label>
                    </div>

                    <div class="field-grid field-grid--4">
                        <label class="field">
                            <span>Chunk size</span>
                            <input v-model.number="settingsForm.knowledge.max_chunk_characters" type="number" min="100">
                        </label>

                        <label class="field">
                            <span>Chunk overlap</span>
                            <input v-model.number="settingsForm.knowledge.chunk_overlap_characters" type="number" min="0">
                        </label>

                        <label class="field">
                            <span>Minimum similarity</span>
                            <input v-model.number="settingsForm.knowledge.min_similarity" type="number" min="0" max="1" step="0.01">
                        </label>

                        <label class="field">
                            <span>YouTube timeout</span>
                            <input v-model.number="settingsForm.youtube.timeout" type="number" min="1">
                        </label>
                    </div>

                    <div class="field-grid field-grid--4">
                        <label class="field">
                            <span>Widget position</span>
                            <select v-model="settingsForm.widget.position">
                                <option
                                    v-for="position in widgetPositions"
                                    :key="position.value"
                                    :value="position.value"
                                >
                                    {{ position.label }}
                                </option>
                            </select>
                        </label>

                        <label class="field">
                            <span>Widget width</span>
                            <input v-model="settingsForm.widget.width" type="text">
                        </label>

                        <label class="field">
                            <span>Primary color</span>
                            <input v-model="settingsForm.widget.primary_color" type="color">
                        </label>

                        <label class="field">
                            <span>Accent color</span>
                            <input v-model="settingsForm.widget.accent_color" type="color">
                        </label>
                    </div>

                    <div class="field-grid field-grid--4">
                        <label class="field">
                            <span>Button text color</span>
                            <input v-model="settingsForm.widget.button_text_color" type="color">
                        </label>

                        <label class="field">
                            <span>Muted surface color</span>
                            <input v-model="settingsForm.widget.surface_color" type="color">
                        </label>

                        <label class="field">
                            <span>Muted text color</span>
                            <input v-model="settingsForm.widget.surface_text_color" type="color">
                        </label>

                        <label class="field">
                            <span>Muted border color</span>
                            <input v-model="settingsForm.widget.border_color" type="color">
                        </label>
                    </div>

                    <p class="field-note">
                        These colors control the main send buttons plus the softer assistant bubbles, chips, and citations.
                    </p>

                    <div class="field-grid field-grid--2">
                        <label class="field">
                            <span>Header label</span>
                            <input v-model="settingsForm.widget.eyebrow_label" type="text">
                        </label>

                        <label class="field">
                            <span>Launcher label (optional)</span>
                            <input
                                v-model="settingsForm.widget.launcher_label"
                                type="text"
                                placeholder="Leave blank for icon only by default"
                            >
                        </label>

                        <label class="field">
                            <span>Welcome title</span>
                            <input v-model="settingsForm.widget.welcome_title" type="text">
                        </label>
                    </div>

                    <label class="field">
                        <span>Welcome message</span>
                        <textarea v-model="settingsForm.widget.welcome_message" rows="3"></textarea>
                    </label>

                    <div class="field-grid field-grid--2">
                        <label class="field">
                            <span>Support hours</span>
                            <input v-model="settingsForm.widget.support_hours" type="text">
                        </label>

                        <label class="field">
                            <span>Logo URL</span>
                            <input v-model="settingsForm.widget.logo_url" type="url">
                        </label>
                    </div>

                    <label class="field">
                        <span>Privacy notice</span>
                        <textarea v-model="settingsForm.widget.privacy_notice" rows="2"></textarea>
                    </label>

                    <div class="field-grid field-grid--3">
                        <label class="field checkbox-field">
                            <span>Store leads in database</span>
                            <input v-model="settingsForm.lead_destinations.database" type="checkbox">
                        </label>

                        <label class="field checkbox-field">
                            <span>Email destination</span>
                            <input v-model="settingsForm.lead_destinations.email.enabled" type="checkbox">
                        </label>

                        <label class="field">
                            <span>Email recipient</span>
                            <input v-model="settingsForm.lead_destinations.email.to" type="email">
                        </label>
                    </div>

                    <div class="field-grid field-grid--3">
                        <label class="field checkbox-field">
                            <span>Webhook destination</span>
                            <input v-model="settingsForm.lead_destinations.webhook.enabled" type="checkbox">
                        </label>

                        <label class="field">
                            <span>Webhook URL</span>
                            <input v-model="settingsForm.lead_destinations.webhook.url" type="url">
                        </label>

                        <label class="field">
                            <span>Webhook secret</span>
                            <input v-model="settingsForm.lead_destinations.webhook.secret" type="password" autocomplete="off">
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <section class="crud-grid">
            <article class="panel">
                <header class="panel-header">
                    <div>
                        <p class="panel-kicker">Profiles</p>
                        <h2>Bot profiles</h2>
                    </div>
                    <button class="action-button" @click="startNewProfile">
                        New profile
                    </button>
                </header>

                <div class="editor-grid">
                    <div class="list-stack">
                        <button
                            v-for="profile in dashboard.profiles"
                            :key="profile.id"
                            class="list-card"
                            :class="{ 'list-card--active': profile.id === selectedProfileId }"
                            @click="selectedProfileId = profile.id"
                        >
                            <strong>{{ profile.name }}</strong>
                            <span>{{ profile.handle }}</span>
                            <span>{{ profile.site || 'All sites' }} · {{ profile.locale || 'All locales' }}</span>
                            <span>{{ profile.active ? 'Active' : 'Paused' }} · {{ profile.faq_items_count }} FAQs · {{ profile.source_connections_count }} sources</span>
                        </button>
                    </div>

                    <div class="stack">
                        <div class="field-grid field-grid--4">
                            <label class="field">
                                <span>Name</span>
                                <input v-model="profileForm.name" type="text">
                            </label>

                            <label class="field">
                                <span>Handle</span>
                                <input v-model="profileForm.handle" type="text">
                            </label>

                            <label class="field">
                                <span>Site</span>
                                <input v-model="profileForm.site" type="text" list="site-handles">
                            </label>

                            <label class="field">
                                <span>Locale</span>
                                <input v-model="profileForm.locale" type="text">
                            </label>
                        </div>

                        <div class="field-grid field-grid--4">
                            <label class="field checkbox-field">
                                <span>Active</span>
                                <input v-model="profileForm.active" type="checkbox">
                            </label>

                            <label class="field checkbox-field">
                                <span>Default profile</span>
                                <input v-model="profileForm.is_default" type="checkbox">
                            </label>

                            <label class="field checkbox-field">
                                <span>Lead capture enabled</span>
                                <input v-model="profileForm.lead_settings.enabled" type="checkbox">
                            </label>

                            <label class="field">
                                <span>Brand voice</span>
                                <input v-model="profileForm.branding.voice" type="text">
                            </label>
                        </div>

                        <div class="field-grid field-grid--3">
                            <label class="field">
                                <span>Text provider override</span>
                                <select v-model="profileForm.provider_overrides.text.driver">
                                    <option value="">Use global</option>
                                    <option
                                        v-for="provider in dashboard.providerOptions"
                                        :key="provider.key"
                                        :value="provider.key"
                                    >
                                        {{ provider.label }}
                                    </option>
                                </select>
                            </label>

                            <label class="field">
                                <span>Text model override</span>
                                <input v-model="profileForm.provider_overrides.text.model" type="text">
                            </label>

                            <label class="field">
                                <span>Embeddings provider override</span>
                                <select v-model="profileForm.provider_overrides.embeddings.driver">
                                    <option value="">Use global</option>
                                    <option
                                        v-for="provider in dashboard.providerOptions"
                                        :key="provider.key"
                                        :value="provider.key"
                                    >
                                        {{ provider.label }}
                                    </option>
                                </select>
                            </label>
                        </div>

                        <div class="field-grid field-grid--4">
                            <label class="field">
                                <span>Embeddings model override</span>
                                <input v-model="profileForm.provider_overrides.embeddings.model" type="text">
                            </label>

                            <label class="field">
                                <span>Embeddings dimensions</span>
                                <input v-model.number="profileForm.provider_overrides.embeddings.dimensions" type="number" min="1">
                            </label>

                            <label class="field checkbox-field">
                                <span>Embeddings enabled override</span>
                                <input v-model="profileForm.provider_overrides.embeddings.enabled" type="checkbox">
                            </label>

                            <label class="field">
                                <span>Widget position override</span>
                                <select v-model="profileForm.widget_settings.position">
                                    <option value="">Use global</option>
                                    <option
                                        v-for="position in widgetPositions"
                                        :key="position.value"
                                        :value="position.value"
                                    >
                                        {{ position.label }}
                                    </option>
                                </select>
                            </label>
                        </div>

                        <div class="field-grid field-grid--4">
                            <label class="field">
                                <span>Widget width</span>
                                <input v-model="profileForm.widget_settings.width" type="text">
                            </label>

                            <label class="field">
                                <span>Header label override</span>
                                <input v-model="profileForm.widget_settings.eyebrow_label" type="text">
                            </label>

                            <label class="field">
                                <span>Launcher label override (optional)</span>
                                <input
                                    v-model="profileForm.widget_settings.launcher_label"
                                    type="text"
                                    placeholder="Leave blank to inherit the global launcher label"
                                >
                            </label>

                            <label class="field">
                                <span>Welcome title</span>
                                <input v-model="profileForm.widget_settings.welcome_title" type="text">
                            </label>

                            <label class="field">
                                <span>Logo URL</span>
                                <input v-model="profileForm.widget_settings.logo_url" type="url">
                            </label>
                        </div>

                        <p class="field-note">
                            Profile widget settings override the global widget defaults. Leave a field blank here to inherit the global value.
                        </p>

                        <div class="field-grid field-grid--2">
                            <label class="field">
                                <span>Primary color</span>
                                <input v-model="profileForm.widget_settings.primary_color" type="color">
                            </label>

                            <label class="field">
                                <span>Accent color</span>
                                <input v-model="profileForm.widget_settings.accent_color" type="color">
                            </label>
                        </div>

                        <label class="field">
                            <span>Welcome message</span>
                            <textarea v-model="profileForm.widget_settings.welcome_message" rows="3"></textarea>
                        </label>

                        <div class="field-grid field-grid--2">
                            <label class="field">
                                <span>Support label</span>
                                <input v-model="profileForm.support_settings.label" type="text">
                            </label>

                            <label class="field">
                                <span>Support URL</span>
                                <input v-model="profileForm.support_settings.contact_url" type="url">
                            </label>
                        </div>

                        <div class="field-grid field-grid--2">
                            <label class="field">
                                <span>Support email</span>
                                <input v-model="profileForm.support_settings.email" type="email">
                            </label>

                            <label class="field">
                                <span>Support phone</span>
                                <input v-model="profileForm.support_settings.phone" type="text">
                            </label>
                        </div>

                        <div class="field-grid field-grid--2">
                            <label class="field">
                                <span>Lead headline</span>
                                <input v-model="profileForm.lead_settings.headline" type="text">
                            </label>

                            <label class="field">
                                <span>Lead description</span>
                                <input v-model="profileForm.lead_settings.description" type="text">
                            </label>
                        </div>

                        <label class="field">
                            <span>System prompt</span>
                            <textarea v-model="profileForm.system_prompt" rows="6"></textarea>
                        </label>

                        <div class="form-actions">
                            <button
                                class="action-button action-button--primary"
                                :disabled="busy.profile"
                                @click="saveProfile"
                            >
                                {{ busy.profile ? 'Saving…' : 'Save profile' }}
                            </button>

                            <button
                                class="action-button action-button--danger"
                                :disabled="busy.profile || !profileForm.id"
                                @click="deleteProfile"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </article>

            <article class="panel">
                <header class="panel-header">
                    <div>
                        <p class="panel-kicker">FAQs</p>
                        <h2>Curated answers</h2>
                    </div>
                    <button class="action-button" :disabled="!hasProfiles" @click="startNewFaq">
                        New FAQ
                    </button>
                </header>

                <div class="editor-grid">
                    <div class="list-stack">
                        <button
                            v-for="faq in dashboard.faqs"
                            :key="faq.id"
                            class="list-card"
                            :class="{ 'list-card--active': faq.id === selectedFaqId }"
                            @click="selectedFaqId = faq.id"
                        >
                            <strong>{{ faq.question }}</strong>
                            <span>{{ faq.profile.name }} · Priority {{ faq.priority }}</span>
                            <span>{{ faq.active ? 'Active' : 'Paused' }}</span>
                        </button>
                    </div>

                    <div class="stack">
                        <div class="field-grid field-grid--4">
                            <label class="field">
                                <span>Profile</span>
                                <select v-model="faqForm.bot_profile_id" :disabled="!hasProfiles">
                                    <option
                                        v-for="profile in dashboard.profiles"
                                        :key="profile.id"
                                        :value="profile.id"
                                    >
                                        {{ profile.name }}
                                    </option>
                                </select>
                            </label>

                            <label class="field">
                                <span>Site</span>
                                <input v-model="faqForm.site" type="text" list="site-handles">
                            </label>

                            <label class="field">
                                <span>Locale</span>
                                <input v-model="faqForm.locale" type="text">
                            </label>

                            <label class="field">
                                <span>Priority</span>
                                <input v-model.number="faqForm.priority" type="number" min="0">
                            </label>
                        </div>

                        <div class="field-grid field-grid--2">
                            <label class="field">
                                <span>Question</span>
                                <input v-model="faqForm.question" type="text">
                            </label>

                            <label class="field checkbox-field">
                                <span>Active</span>
                                <input v-model="faqForm.active" type="checkbox">
                            </label>
                        </div>

                        <label class="field">
                            <span>Question variants</span>
                            <textarea
                                v-model="faqForm.question_variants_text"
                                rows="2"
                                placeholder="Comma or line separated"
                            ></textarea>
                        </label>

                        <label class="field">
                            <span>Answer</span>
                            <textarea v-model="faqForm.answer" rows="6"></textarea>
                        </label>

                        <label class="field">
                            <span>Lead capture fields</span>
                            <input
                                v-model="faqForm.lead_capture_fields_text"
                                type="text"
                                placeholder="name, email, message"
                            >
                        </label>

                        <div class="stack">
                            <div class="subheader">
                                <h3>CTA actions</h3>
                                <button class="action-button action-button--ghost" @click="addCtaAction">
                                    Add action
                                </button>
                            </div>

                            <div
                                v-for="(action, index) in faqForm.cta_actions"
                                :key="index"
                                class="nested-card"
                            >
                                <div class="field-grid field-grid--4">
                                    <label class="field">
                                        <span>Type</span>
                                        <select v-model="action.type">
                                            <option value="link">Link</option>
                                            <option value="lead_capture">Lead capture</option>
                                            <option value="email">Email</option>
                                            <option value="phone">Phone</option>
                                        </select>
                                    </label>

                                    <label class="field">
                                        <span>Label</span>
                                        <input v-model="action.label" type="text">
                                    </label>

                                    <label class="field">
                                        <span>URL</span>
                                        <input v-model="action.url" type="text">
                                    </label>

                                    <label class="field">
                                        <span>Value</span>
                                        <input v-model="action.value" type="text">
                                    </label>
                                </div>

                                <button class="action-button action-button--danger" @click="removeCtaAction(index)">
                                    Remove action
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button
                                class="action-button action-button--primary"
                                :disabled="busy.faq || !hasProfiles"
                                @click="saveFaq"
                            >
                                {{ busy.faq ? 'Saving…' : 'Save FAQ' }}
                            </button>

                            <button
                                class="action-button action-button--danger"
                                :disabled="busy.faq || !faqForm.id"
                                @click="deleteFaq"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </article>

            <article class="panel">
                <header class="panel-header">
                    <div>
                        <p class="panel-kicker">Sources</p>
                        <h2>Knowledge connections</h2>
                    </div>
                    <button class="action-button" :disabled="!hasProfiles" @click="startNewSource">
                        New source
                    </button>
                </header>

                <div class="editor-grid">
                    <div class="list-stack">
                        <button
                            v-for="source in dashboard.sources"
                            :key="source.id"
                            class="list-card"
                            :class="{ 'list-card--active': source.id === selectedSourceId }"
                            @click="selectedSourceId = source.id"
                        >
                            <strong>{{ source.name }}</strong>
                            <span>{{ source.driver }} · {{ source.profile.name }}</span>
                            <span>{{ source.status }} · {{ source.knowledge_documents_count }} docs</span>
                        </button>
                    </div>

                    <div class="stack">
                        <div class="field-grid field-grid--4">
                            <label class="field">
                                <span>Profile</span>
                                <select v-model="sourceForm.bot_profile_id" :disabled="!hasProfiles">
                                    <option
                                        v-for="profile in dashboard.profiles"
                                        :key="profile.id"
                                        :value="profile.id"
                                    >
                                        {{ profile.name }}
                                    </option>
                                </select>
                            </label>

                            <label class="field">
                                <span>Name</span>
                                <input v-model="sourceForm.name" type="text">
                            </label>

                            <label class="field">
                                <span>Driver</span>
                                <select v-model="sourceForm.driver">
                                    <option
                                        v-for="driver in dashboard.drivers"
                                        :key="driver.key"
                                        :value="driver.key"
                                    >
                                        {{ driver.label }}
                                    </option>
                                </select>
                            </label>

                            <label class="field checkbox-field">
                                <span>Active</span>
                                <input v-model="sourceForm.active" type="checkbox">
                            </label>
                        </div>

                        <template v-if="sourceForm.driver === 'statamic'">
                            <div class="field-grid field-grid--2">
                                <label class="field">
                                    <span>Sites</span>
                                    <textarea v-model="sourceForm.statamic.sites_text" rows="2" placeholder="Comma or line separated"></textarea>
                                </label>

                                <label class="field">
                                    <span>Collections</span>
                                    <textarea v-model="sourceForm.statamic.collections_text" rows="2" placeholder="pages, blog"></textarea>
                                </label>
                            </div>

                            <div class="field-grid field-grid--3">
                                <label class="field">
                                    <span>Globals</span>
                                    <textarea v-model="sourceForm.statamic.globals_text" rows="2"></textarea>
                                </label>

                                <label class="field">
                                    <span>Navs</span>
                                    <textarea v-model="sourceForm.statamic.navs_text" rows="2"></textarea>
                                </label>

                                <label class="field">
                                    <span>Taxonomies</span>
                                    <textarea v-model="sourceForm.statamic.taxonomies_text" rows="2"></textarea>
                                </label>
                            </div>
                        </template>

                        <template v-else>
                            <div class="subheader">
                                <h3>YouTube transcript items</h3>
                                <button class="action-button action-button--ghost" @click="addYouTubeItem">
                                    Add item
                                </button>
                            </div>

                            <div
                                v-for="(item, index) in sourceForm.youtube_items"
                                :key="index"
                                class="nested-card"
                            >
                                <div class="field-grid field-grid--2">
                                    <label class="field">
                                        <span>YouTube URL</span>
                                        <input v-model="item.url" type="url">
                                    </label>

                                    <label class="field">
                                        <span>Timestamp note</span>
                                        <input v-model="item.timestamp" type="text">
                                    </label>
                                </div>

                                <label class="field">
                                    <span>Transcript</span>
                                    <textarea v-model="item.transcript" rows="5"></textarea>
                                </label>

                                <button class="action-button action-button--danger" @click="removeYouTubeItem(index)">
                                    Remove item
                                </button>
                            </div>
                        </template>

                        <div class="form-actions">
                            <button
                                class="action-button action-button--primary"
                                :disabled="busy.source || !hasProfiles"
                                @click="saveSource"
                            >
                                {{ busy.source ? 'Saving…' : 'Save source' }}
                            </button>

                            <button
                                class="action-button"
                                :disabled="busy.sourceSync || !sourceForm.id"
                                @click="syncSource(sourceForm.id)"
                            >
                                {{ busy.sourceSync ? 'Syncing…' : 'Sync source' }}
                            </button>

                            <button
                                class="action-button action-button--danger"
                                :disabled="busy.source || !sourceForm.id"
                                @click="deleteSource"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="operations-grid">
            <article class="panel">
                <header class="panel-header">
                    <div>
                        <p class="panel-kicker">Chats</p>
                        <h2>Conversation review</h2>
                    </div>
                </header>

                <div class="editor-grid editor-grid--chat">
                    <div class="list-stack">
                        <button
                            v-for="conversation in dashboard.conversations"
                            :key="conversation.id"
                            class="list-card"
                            :class="{ 'list-card--active': conversation.id === selectedConversationId }"
                            @click="selectedConversationId = conversation.id"
                        >
                            <strong>{{ conversation.visitor_name || conversation.visitor_email || conversation.session_id }}</strong>
                            <span>{{ conversation.profile.name }} · {{ conversation.messages_count }} messages</span>
                            <span>{{ conversation.site || 'All sites' }} · {{ conversation.locale || 'All locales' }}</span>
                        </button>
                    </div>

                    <div class="stack">
                        <template v-if="selectedConversation">
                            <div class="detail-header">
                                <div>
                                    <h3>{{ selectedConversation.visitor_name || 'Anonymous visitor' }}</h3>
                                    <p>{{ selectedConversation.visitor_email || selectedConversation.session_id }}</p>
                                </div>

                                <button
                                    class="action-button action-button--danger"
                                    :disabled="busy.conversation"
                                    @click="deleteConversation(selectedConversation.id)"
                                >
                                    Delete
                                </button>
                            </div>

                            <div class="message-thread">
                                <article
                                    v-for="message in selectedConversation.messages"
                                    :key="message.id"
                                    class="message-card"
                                    :class="`message-card--${message.role}`"
                                >
                                    <header>
                                        <strong>{{ message.role }}</strong>
                                        <small>{{ message.created_at }}</small>
                                    </header>
                                    <p>{{ message.content }}</p>
                                </article>
                            </div>
                        </template>

                        <p v-else class="empty-state">
                            Conversations will appear here once visitors start chatting.
                        </p>
                    </div>
                </div>
            </article>

            <article class="panel">
                <header class="panel-header">
                    <div>
                        <p class="panel-kicker">Leads</p>
                        <h2>Lead capture desk</h2>
                    </div>
                    <button class="action-button" :disabled="!hasProfiles" @click="startNewLead">
                        New lead
                    </button>
                </header>

                <div class="editor-grid">
                    <div class="list-stack">
                        <button
                            v-for="lead in dashboard.leads"
                            :key="lead.id"
                            class="list-card"
                            :class="{ 'list-card--active': lead.id === selectedLeadId }"
                            @click="selectedLeadId = lead.id"
                        >
                            <strong>{{ lead.name }}</strong>
                            <span>{{ lead.email }}</span>
                            <span>{{ lead.profile.name }} · {{ lead.status }}</span>
                        </button>
                    </div>

                    <div class="stack">
                        <div class="field-grid field-grid--4">
                            <label class="field">
                                <span>Profile</span>
                                <select v-model="leadForm.bot_profile_id" :disabled="!hasProfiles">
                                    <option
                                        v-for="profile in dashboard.profiles"
                                        :key="profile.id"
                                        :value="profile.id"
                                    >
                                        {{ profile.name }}
                                    </option>
                                </select>
                            </label>

                            <label class="field">
                                <span>Status</span>
                                <select v-model="leadForm.status">
                                    <option
                                        v-for="status in leadStatuses"
                                        :key="status.value"
                                        :value="status.value"
                                    >
                                        {{ status.label }}
                                    </option>
                                </select>
                            </label>

                            <label class="field">
                                <span>Site</span>
                                <input v-model="leadForm.site" type="text" list="site-handles">
                            </label>

                            <label class="field">
                                <span>Locale</span>
                                <input v-model="leadForm.locale" type="text">
                            </label>
                        </div>

                        <div class="field-grid field-grid--3">
                            <label class="field">
                                <span>Name</span>
                                <input v-model="leadForm.name" type="text">
                            </label>

                            <label class="field">
                                <span>Email</span>
                                <input v-model="leadForm.email" type="email">
                            </label>

                            <label class="field">
                                <span>Phone</span>
                                <input v-model="leadForm.phone" type="text">
                            </label>
                        </div>

                        <label class="field">
                            <span>Conversation ID</span>
                            <input v-model="leadForm.chat_conversation_id" type="number" min="1">
                        </label>

                        <label class="field">
                            <span>Message</span>
                            <textarea v-model="leadForm.message" rows="6"></textarea>
                        </label>

                        <div class="form-actions">
                            <button
                                class="action-button action-button--primary"
                                :disabled="busy.lead || !hasProfiles"
                                @click="saveLead"
                            >
                                {{ busy.lead ? 'Saving…' : 'Save lead' }}
                            </button>

                            <button
                                class="action-button action-button--danger"
                                :disabled="busy.lead || !leadForm.id"
                                @click="deleteLead"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <datalist id="site-handles">
            <option
                v-for="site in dashboard.sites"
                :key="site.handle"
                :value="site.handle"
            >
                {{ site.name }}
            </option>
        </datalist>
    </div>
</template>
