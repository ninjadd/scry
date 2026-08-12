import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router.js';
import './app.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

const baseApiUrl = window.ScryConfig?.baseApiUrl || '/scry/api';
app.provide('baseApiUrl', baseApiUrl);

app.mount('#app');
