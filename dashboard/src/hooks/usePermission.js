import { hasPermission } from '../utils/permissions';
import { useAuth } from '../auth/AuthContext';

/** Hook pratique pour verifier une permission dans les composants. */
export function usePermission(permission) {
  const { user } = useAuth();
  return hasPermission(user, permission);
}
