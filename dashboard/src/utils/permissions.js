/**
 * Utilitaires permissions — alignes sur PermissionName.php (backend).
 */

/** Verifie si l'utilisateur possede au moins une des permissions listees. */
export function hasPermission(user, permission) {
  if (!user?.permissions) return false;
  const perms = user.permissions;
  if (Array.isArray(permission)) {
    return permission.some((p) => perms.includes(p));
  }
  return perms.includes(permission);
}

/** Verifie si l'utilisateur a le role admin. */
export function isAdmin(user) {
  return user?.roles?.includes('admin') ?? false;
}
