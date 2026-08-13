import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useConnectionStore = defineStore('connection', () => {
  const currentConnection = ref(localStorage.getItem('scry-connection') || 'pgsql');
  const availableConnections = ref(['pgsql', 'mysql', 'mariadb', 'sqlite', 'sqlsrv']);
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
    if (conns && Array.isArray(conns) && conns.length > 0) {
      availableConnections.value = conns;
      if (!conns.includes(currentConnection.value)) {
        currentConnection.value = conns[0];
        localStorage.setItem('scry-connection', conns[0]);
      }
    }
  };

  const fetchServerStats = async () => {
    loadingStats.value = true;
    try {
      const res = await scryFetch('/server/stats');
      if (res.ok) {
        const data = await res.json();
        serverStats.value = data;
        driver.value = data.driver || driver.value;
        if (data.available_connections) {
          setAvailableConnections(data.available_connections);
        }
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
