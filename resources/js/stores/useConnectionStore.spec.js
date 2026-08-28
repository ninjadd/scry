import { describe, it, expect, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useConnectionStore } from './useConnectionStore';

describe('useConnectionStore.setAvailableConnections', () => {
  beforeEach(() => {
    window.ScryConfig = { baseApiUrl: '/scry/api' };
    localStorage.clear();
    setActivePinia(createPinia());
  });

  it('keeps the current connection when it is only temporarily missing from availableConnections', () => {
    // A connectivity-probe blip in DatabaseExplorerManager::getAvailableConnections()
    // can drop the active connection from the reachable-connections list it
    // returns. That must not silently reassign (and persist) the user's active
    // connection.
    const store = useConnectionStore();
    store.currentConnection = 'primary';
    localStorage.setItem('scry-connection', 'primary');

    store.setAvailableConnections(['secondary', 'tertiary']);

    expect(store.currentConnection).toBe('primary');
    expect(localStorage.getItem('scry-connection')).toBe('primary');
  });

  it('falls back to the first available connection when nothing was previously selected', () => {
    const store = useConnectionStore();
    store.currentConnection = null;

    store.setAvailableConnections(['secondary', 'tertiary']);

    expect(store.currentConnection).toBe('secondary');
    expect(localStorage.getItem('scry-connection')).toBe('secondary');
  });

  it('switches to the reported active connection when it is present in availableConnections', () => {
    const store = useConnectionStore();
    store.currentConnection = 'primary';

    store.setAvailableConnections(['primary', 'secondary'], 'secondary');

    expect(store.currentConnection).toBe('secondary');
    expect(localStorage.getItem('scry-connection')).toBe('secondary');
  });
});
