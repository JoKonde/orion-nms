import { USER_ROLES } from './constants';

/** Formate une date ISO en affichage local. */
export function formatDate(iso) {
  if (!iso) return '—';
  try {
    return new Date(iso).toLocaleString('fr-FR');
  } catch {
    return iso;
  }
}

/** Capitalise et remplace underscores. */
export function formatLabel(value) {
  if (!value) return '—';
  return String(value).replace(/_/g, ' ');
}

/** Libelle francais d'un role ORION. */
export function formatRole(role) {
  if (!role) return '—';
  const found = USER_ROLES.find((r) => r.value === role);
  return found?.label ?? formatLabel(role);
}
