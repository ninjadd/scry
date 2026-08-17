import { createRouter, createWebHistory } from 'vue-router';
import DashboardView from './views/DashboardView.vue';
import TableBrowserView from './views/TableBrowserView.vue';
import DataGridView from './views/DataGridView.vue';
import QueryRunnerView from './views/QueryRunnerView.vue';
import QueryBuilderQBEView from './views/QueryBuilderQBEView.vue';
import SchemaVisualizerERDView from './views/SchemaVisualizerERDView.vue';
import ServerTuningView from './views/ServerTuningView.vue';
import GlobalSearchView from './views/GlobalSearchView.vue';
import UserManagementView from './views/UserManagementView.vue';
import ImportExportView from './views/ImportExportView.vue';
import RoutinesView from './views/RoutinesView.vue';
import SchemaView from './components/SchemaView.vue';

const basePath = window.ScryConfig?.basePath || '/scry';

const routes = [
  { path: '/', name: 'dashboard', component: DashboardView },
  { path: '/dashboard', name: 'dashboard-alt', component: DashboardView },
  { path: '/tables', name: 'tables', component: TableBrowserView },
  { path: '/tables/:table/data', name: 'data', component: DataGridView, props: true },
  { path: '/tables/:table/schema', name: 'schema', component: SchemaView, props: true },
  { path: '/qbe', name: 'qbe', component: QueryBuilderQBEView },
  { path: '/query-builder', name: 'query-builder', component: QueryBuilderQBEView },
  { path: '/erd', name: 'erd', component: SchemaVisualizerERDView },
  { path: '/query', name: 'query', component: QueryRunnerView },
  { path: '/console', name: 'console', component: QueryRunnerView },
  { path: '/tuning', name: 'tuning', component: ServerTuningView },
  { path: '/search', name: 'search', component: GlobalSearchView },
  { path: '/users', name: 'users', component: UserManagementView },
  { path: '/routines', name: 'routines', component: RoutinesView },
  { path: '/import-export', name: 'import-export', component: ImportExportView },
];

const router = createRouter({
  history: createWebHistory(basePath),
  routes,
});

export default router;
