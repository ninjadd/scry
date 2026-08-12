import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import TableList from './components/TableList.vue';
import SchemaView from './components/SchemaView.vue';
import DataView from './components/DataView.vue';
import QueryConsole from './components/QueryConsole.vue';
import './app.css';

const appElement = document.getElementById('app');
const basePath = appElement?.getAttribute('data-base-path') || '/db-manager';

const routes = [
  { path: '/', name: 'tables', component: TableList },
  { path: '/table/:table/schema', name: 'schema', component: SchemaView, props: true },
  { path: '/table/:table/data', name: 'data', component: DataView, props: true },
  { path: '/console', name: 'console', component: QueryConsole },
];

const router = createRouter({
  history: createWebHistory(basePath),
  routes,
});

const app = createApp(App);
app.provide('baseApiUrl', `${basePath}/api`);
app.use(router);
app.mount('#app');
