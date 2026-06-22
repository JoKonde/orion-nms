/**
 * Formate le message affiche apres un scan reseau (page Reseau / Topologie).
 */
export function formatNetworkScanMessage(result) {
  if (!result) return '';

  if (result.error && result.success === false) {
    return result.error;
  }

  const hosts = result.hosts_found ?? 0;
  const created = result.devices_created ?? 0;
  const updated = result.devices_updated ?? 0;

  let msg = `${result.message || 'Scan terminé.'} ${hosts} hôte(s) trouvé(s), ${created} nouveau(x), ${updated} mis à jour.`;

  if (result.warning) {
    msg += ` ${result.warning}`;
  }

  if (result.nmap_binary) {
    msg += ` (Nmap: ${result.nmap_binary})`;
  }

  return msg;
}
