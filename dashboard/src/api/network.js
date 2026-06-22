import { apiClient } from './client';

export async function fetchNetworkDetected() {
  const { data } = await apiClient.get('/network/detected');
  return data;
}

export async function discoverNetwork(subnet) {
  const payload = subnet ? { subnet } : {};
  const { data } = await apiClient.post('/network/discover', payload);
  return data;
}
