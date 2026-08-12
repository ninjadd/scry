import { createRouter, createWebHistory } from 'vue-router';
import DashboardView from './views/DashboardView.vue';
import TableBrowserView from './views/TableBrowserView.vue';
import DataGridView from './views/DataGridView.vue';
import QueryRunnerView from './views/QueryRunnerView.vue';
import SchemaView from './components/SchemaView.vue';

const basePath = window.ScryConfig?.basePath || '/scry';

const routes = [
  { path: '/', name: 'dashboard', component: DashboardView },
  { path: '/dashboard', name: 'dashboard-alt', component: DashboardView },
  { path: '/tables', name: 'tables', component: TableBrowserView },
  { path: '/tables/:table/data', name: 'data', component: DataGridView, props: true },
  { path: '/tables/:table/schema', name: 'schema', component: SchemaView, props: true },
  { path: '/query', name: 'query', component: QueryRunnerView },
  { path: '/console', name: 'console', component: QueryRunnerView },
];

const router = createRouter({
  history: createWebHistory(basePath),
  routes,
});

export default router;
