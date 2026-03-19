<script setup>
import { computed, ref } from 'vue';

function createSessionId() {
    if (typeof globalThis.crypto?.randomUUID === 'function') {
        return globalThis.crypto.randomUUID();
    }

    if (typeof globalThis.crypto?.getRandomValues === 'function') {
        const bytes = globalThis.crypto.getRandomValues(new Uint8Array(16));

        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;

        const hex = Array.from(bytes, (value) => value.toString(16).padStart(2, '0')).join('');

        return [
            hex.slice(0, 8),
            hex.slice(8, 12),
            hex.slice(12, 16),
            hex.slice(16, 20),
            hex.slice(20),
        ].join('-');
    }

    return `session-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
});

const open = ref(false);
const loading = ref(false);
const showLeadForm = ref(false);
const conversationId = ref(null);
const sessionId = ref(createSessionId());
const draft = ref('');
const lead = ref({
    name: '',
    email: '',
    phone: '',
    message: '',
});
const messages = ref([
    {
        role: 'assistant',
        content: props.config.welcome_message,
    },
]);
const actions = ref([]);

const styleVars = computed(() => ({
    '--chatbot-primary': props.config.primary_color ?? '#0f766e',
    '--chatbot-accent': props.config.accent_color ?? '#f4a261',
    '--chatbot-width': props.config.width ?? '26rem',
}));

const launcherLabel = computed(() => String(props.config.launcher_label ?? '').trim());
const eyebrowLabel = computed(() => String(props.config.eyebrow_label ?? '').trim());

const launcherAriaLabel = computed(() => (
    launcherLabel.value
        || String(props.config.welcome_title ?? '').trim()
        || 'Open chat'
));

const launcherClasses = computed(() => [
    'aesircloud-chatbot-launcher',
    `is-${props.config.position ?? 'bottom-right'}`,
    { 'is-icon-only': launcherLabel.value === '' },
]);

function csrfToken() {
    return props.config.csrf_token
        ?? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        ?? '';
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

async function parseJsonResponse(response) {
    const payload = await response.json();

    if (!response.ok) {
        throw new Error(payload?.message ?? 'Request failed.');
    }

    return payload;
}

function assistantContent(payload) {
    if (payload?.status === 'degraded') {
        if (payload?.error_code === 'ai_provider_misconfigured') {
            return payload.message || 'I am having trouble with the AI setup right now. You can still contact support or leave your details for a follow-up.';
        }

        return payload.message || 'I am having trouble reaching the AI assistant right now. You can still contact support or leave your details for a follow-up.';
    }

    return payload?.message || 'I ran into an issue answering that. You can still contact support or leave your details for a follow-up.';
}

async function sendMessage() {
    if (!draft.value.trim() || loading.value) {
        return;
    }

    const message = draft.value.trim();

    messages.value.push({
        role: 'user',
        content: message,
    });

    draft.value = '';
    loading.value = true;

    try {
        const response = await fetch(props.config.chat_endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: requestHeaders(),
            body: JSON.stringify({
                profile: props.config.profile,
                site: props.config.site,
                locale: props.config.locale,
                session_id: sessionId.value,
                path: window.location.pathname,
                message,
            }),
        });

        const payload = await parseJsonResponse(response);

        conversationId.value = payload.conversation_id;
        actions.value = payload.next_actions ?? [];
        showLeadForm.value = (payload.lead_capture_fields ?? []).length > 0 || (payload.intent === 'lead_capture');

        messages.value.push({
            role: 'assistant',
            content: assistantContent(payload),
            status: payload.status ?? 'ok',
            errorCode: payload.error_code ?? null,
            citations: payload.citations ?? [],
        });
    } catch (error) {
        messages.value.push({
            role: 'assistant',
            content: 'I ran into an issue answering that. You can still contact support or leave your details for a follow-up.',
        });
    } finally {
        loading.value = false;
    }
}

async function submitLead() {
    loading.value = true;

    try {
        const response = await fetch(props.config.lead_endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: requestHeaders(),
            body: JSON.stringify({
                profile: props.config.profile,
                site: props.config.site,
                locale: props.config.locale,
                conversation_id: conversationId.value,
                ...lead.value,
            }),
        });

        await parseJsonResponse(response);

        showLeadForm.value = false;
        messages.value.push({
            role: 'assistant',
            content: 'Thanks. Your details are in and someone can follow up with you shortly.',
        });
        lead.value = { name: '', email: '', phone: '', message: '' };
    } catch (error) {
        messages.value.push({
            role: 'assistant',
            content: 'I could not submit your details just now. Please try again or use one of the support links below.',
        });
    } finally {
        loading.value = false;
    }
}

function applyAction(action) {
    if (action.type === 'lead_capture') {
        showLeadForm.value = true;
        return;
    }

    if (action.type === 'email' && action.value) {
        window.location.href = `mailto:${action.value}`;
        return;
    }

    if (action.url) {
        window.open(action.url, '_blank', 'noopener,noreferrer');
    }
}
</script>

<template>
    <div
        class="aesircloud-chatbot-shell"
        :style="styleVars"
    >
        <button
            type="button"
            :class="launcherClasses"
            :aria-label="launcherAriaLabel"
            @click="open = !open"
        >
            <span class="launcher-pulse" />
            <span v-if="launcherLabel">{{ launcherLabel }}</span>
        </button>

        <transition name="chatbot-panel">
            <section
                v-if="open"
                class="aesircloud-chatbot-panel"
            >
                <header class="panel-header">
                    <div>
                        <p v-if="eyebrowLabel" class="eyebrow">{{ eyebrowLabel }}</p>
                        <h2>{{ config.welcome_title }}</h2>
                    </div>

                    <button
                        type="button"
                        class="panel-close"
                        @click="open = false"
                    >
                        ×
                    </button>
                </header>

                <div class="panel-body">
                    <div class="message-stack">
                        <article
                            v-for="(message, index) in messages"
                            :key="index"
                            class="message"
                            :class="[
                                `message--${message.role}`,
                                { 'message--degraded': message.status === 'degraded' },
                            ]"
                        >
                            <div class="message-body">
                                {{ message.content }}
                            </div>

                            <ul
                                v-if="message.citations?.length"
                                class="citation-list"
                            >
                                <li
                                    v-for="(citation, citationIndex) in message.citations"
                                    :key="citationIndex"
                                >
                                    <a
                                        v-if="citation.url"
                                        :href="citation.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ citation.title || citation.url }}
                                    </a>
                                    <span v-else>{{ citation.title || 'Knowledge source' }}</span>
                                </li>
                            </ul>
                        </article>
                    </div>

                    <div
                        v-if="actions.length"
                        class="actions-row"
                    >
                        <button
                            v-for="(action, index) in actions"
                            :key="index"
                            type="button"
                            class="action-button"
                            @click="applyAction(action)"
                        >
                            {{ action.label || 'Continue' }}
                        </button>
                    </div>

                    <form
                        v-if="showLeadForm"
                        class="lead-form"
                        @submit.prevent="submitLead"
                    >
                        <h3>Request a follow-up</h3>
                        <input
                            v-model="lead.name"
                            type="text"
                            placeholder="Your name"
                            required
                            data-gramm="false"
                            data-gramm_editor="false"
                            data-enable-grammarly="false"
                        >
                        <input
                            v-model="lead.email"
                            type="email"
                            placeholder="Email address"
                            required
                            data-gramm="false"
                            data-gramm_editor="false"
                            data-enable-grammarly="false"
                        >
                        <input
                            v-model="lead.phone"
                            type="text"
                            placeholder="Phone (optional)"
                            data-gramm="false"
                            data-gramm_editor="false"
                            data-enable-grammarly="false"
                        >
                        <textarea
                            v-model="lead.message"
                            rows="3"
                            placeholder="What would you like help with?"
                            data-gramm="false"
                            data-gramm_editor="false"
                            data-enable-grammarly="false"
                        />
                        <button
                            type="submit"
                            class="submit-button"
                            :disabled="loading"
                        >
                            {{ loading ? 'Sending…' : 'Send my details' }}
                        </button>
                    </form>

                    <form
                        class="composer"
                        @submit.prevent="sendMessage"
                    >
                        <textarea
                            v-model="draft"
                            rows="2"
                            placeholder="Ask a question, browse FAQs, or request support…"
                            data-gramm="false"
                            data-gramm_editor="false"
                            data-enable-grammarly="false"
                        />
                        <button
                            type="submit"
                            class="submit-button"
                            :disabled="loading"
                        >
                            {{ loading ? 'Thinking…' : 'Send' }}
                        </button>
                    </form>

                    <p
                        v-if="config.privacy_notice"
                        class="privacy-note"
                    >
                        {{ config.privacy_notice }}
                    </p>
                </div>
            </section>
        </transition>
    </div>
</template>
