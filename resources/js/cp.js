import Dashboard from './pages/Dashboard.vue';

Statamic.booting(() => {
    Statamic.$inertia.register('aesircloud-statamic-ai-chatbot::Dashboard', Dashboard);
});
