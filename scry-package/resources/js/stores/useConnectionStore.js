import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useConnectionStore = defineStore('connection', () => {
  const currentConnection = ref(localStorage.getItem('scry-connection') || 'pgsql');
  const availableConnections = ref(['pgsql', 'mysql']);
  const driver = ref('pgsql');
  const serverStats = ref(null);
  const loadingStats = ref(false);

  const baseApiUrl = window.ScryConfig?.baseApiUrl || '/scry/api';

  /**
   * Global fetch wrapper that automatically appends active connection parameter.
   */
  const scryFetch = async (endpoint, options = {}) => {
    const separator = endpoint.includes('?') ? '&' : '?';
    const url = `${baseApiUrl}${endpoint}${separator}connection=${currentConnection.value}`;

    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      ...(options.headers || {}),
    };

    return fetch(url, { ...options, headers });
  };

  const setConnection = async (connectionName) => {
    currentConnection.value = connectionName;
    localStorage.setItem('scry-connection', connectionName);
    await fetchServerStats();
  };

  const setAvailableConnections = (conns) => {
    if (conns && Array.isArray(conns)) {
      availableConnections.value = conns;
    }
  };

  const fetchServerStats = async () => {
    loadingStats.value = true;
    try {
      const res = await scryFetch('/server/stats');
      if (res.ok) {
        serverStats.value = await res.json();
        driver.value = serverStats.value.driver || driver.value;
      }
    } catch (err) {
      console.error('Failed to fetch server stats:', err);
    } finally {
      loadingStats.value = false;
    }
  };

  return {
    currentConnection,
    availableConnections,
    driver,
    serverStats,
    loadingStats,
    baseApiUrl,
    scryFetch,
    setConnection,
    setAvailableConnections,
    fetchServerStats,
  };
});
