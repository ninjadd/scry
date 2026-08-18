import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useConnectionStore = defineStore('connection', () => {
  const configActiveConn = window.ScryConfig?.activeConnection || null;
  const configAvailableConns = window.ScryConfig?.availableConnections || [];
  const storedConn = localStorage.getItem('scry-connection');

  // Validate stored connection against available connections if known
  let initialConn = configActiveConn;
  if (!initialConn) {
    if (storedConn && configAvailableConns.length > 0 && configAvailableConns.includes(storedConn)) {
      initialConn = storedConn;
    } else if (configAvailableConns.length > 0) {
      initialConn = configAvailableConns[0];
    } else {
      initialConn = storedConn || 'default';
    }
  }

  const currentConnection = ref(initialConn);
  const availableConnections = ref(configAvailableConns.length > 0 ? configAvailableConns : (initialConn ? [initialConn] : []));
  const driver = ref(window.ScryConfig?.driver || '');
  const serverStats = ref(null);
  const loadingStats = ref(false);

  const baseApiUrl = window.ScryConfig?.baseApiUrl || '/scry/api';

  /**
   * Global fetch wrapper that automatically appends active connection parameter.
   */
  const scryFetch = async (endpoint, options = {}) => {
    const separator = endpoint.includes('?') ? '&' : '?';
    const connParam = currentConnection.value ? `connection=${encodeURIComponent(currentConnection.value)}` : '';
    const url = connParam ? `${baseApiUrl}${endpoint}${separator}${connParam}` : `${baseApiUrl}${endpoint}`;

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

  const setAvailableConnections = (conns, activeConn = null) => {
    if (conns && Array.isArray(conns) && conns.length > 0) {
      availableConnections.value = conns;
      if (activeConn && conns.includes(activeConn)) {
        currentConnection.value = activeConn;
        localStorage.setItem('scry-connection', activeConn);
      } else if (!currentConnection.value || !conns.includes(currentConnection.value)) {
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
        if (data.connection && data.connection !== currentConnection.value) {
          currentConnection.value = data.connection;
          localStorage.setItem('scry-connection', data.connection);
        }
        if (data.available_connections) {
          setAvailableConnections(data.available_connections, data.connection);
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
