import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { nextTick } from 'vue';
import DataGridView from './DataGridView.vue';
import { useConnectionStore } from '../stores/useConnectionStore';

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

function rowsResponse(rows) {
  return {
    ok: true,
    json: async () => ({
      data: rows,
      meta: { page: 1, current_page: 1, per_page: 25, total: rows.length, last_page: 1 },
    }),
  };
}

describe('DataGridView stale-response guard', () => {
  let store;
  let pendingRowsResolvers;

  beforeEach(() => {
    window.ScryConfig = { baseApiUrl: '/scry/api' };
    setActivePinia(createPinia());
    store = useConnectionStore();

    pendingRowsResolvers = [];

    // Stub the store's fetch wrapper: schema calls resolve immediately, row
    // calls stay pending until the test resolves them explicitly and in
    // whatever order it chooses — this lets us simulate an older request
    // resolving after a newer one.
    store.scryFetch = async (endpoint) => {
      if (endpoint.includes('/schema')) {
        return { ok: true, json: async () => ({ columns: [] }) };
      }
      return new Promise((resolve) => pendingRowsResolvers.push(resolve));
    };
  });

  it('does not let an older in-flight rows response overwrite a newer one', async () => {
    const wrapper = mount(DataGridView, {
      props: { table: 'table_a' },
      global: { stubs: { 'router-link': true } },
    });
    await flush();

    // onMounted triggered the first fetchData() call for table_a — it's now
    // pending (pendingRowsResolvers[0]).
    expect(pendingRowsResolvers).toHaveLength(1);

    // Switching tables triggers the `props.table` watcher, firing a second,
    // newer fetchData() call — it's now pending too (pendingRowsResolvers[1]).
    await wrapper.setProps({ table: 'table_b' });
    await flush();
    expect(pendingRowsResolvers).toHaveLength(2);

    // Resolve the newer (table_b) request first...
    pendingRowsResolvers[1](rowsResponse([{ name: 'fresh-row' }]));
    await flush();
    await nextTick();

    expect(wrapper.text()).toContain('fresh-row');

    // ...then resolve the older (table_a) request, arriving late.
    pendingRowsResolvers[0](rowsResponse([{ name: 'stale-row' }]));
    await flush();
    await nextTick();

    // The stale response must not have overwritten the newer data.
    expect(wrapper.text()).toContain('fresh-row');
    expect(wrapper.text()).not.toContain('stale-row');
  });
});
