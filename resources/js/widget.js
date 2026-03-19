import { createApp } from 'vue';
import WidgetApp from './widget/WidgetApp.vue';

const selector = '[data-aesircloud-statamic-ai-chatbot]';

function mountAll() {
    document.querySelectorAll(selector).forEach((element) => {
        if (element.dataset.aesircloudStatamicAiChatbotMounted === 'true') {
            return;
        }

        const config = JSON.parse(element.dataset.aesircloudStatamicAiChatbot ?? '{}');

        createApp(WidgetApp, { config }).mount(element);

        element.dataset.aesircloudStatamicAiChatbotMounted = 'true';
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAll, { once: true });
} else {
    mountAll();
}

window.AesirCloudStatamicAiChatbot = {
    mount: mountAll,
};
