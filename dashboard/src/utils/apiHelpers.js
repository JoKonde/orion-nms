/**
 * Extrait items + meta d'une reponse Laravel paginee (Resource collection).
 *
 * Format Laravel : { data: [...], meta: {...}, links: {...} }
 * Bug evite : ne pas confondre payload.data (tableau items) avec un wrapper.
 */
export function unwrapPaginated(payload) {
  if (!payload) {
    return { items: [], meta: null, links: null };
  }

  // Cas standard : corps axios = pagination Laravel
  if (Array.isArray(payload.data) && payload.meta) {
    return {
      items: payload.data,
      meta: payload.meta,
      links: payload.links ?? null,
    };
  }

  // Double enveloppe eventuelle { data: { data: [], meta: {} } }
  const inner = payload.data ?? payload;
  if (inner && Array.isArray(inner.data)) {
    return {
      items: inner.data,
      meta: inner.meta ?? null,
      links: inner.links ?? null,
    };
  }

  if (Array.isArray(payload)) {
    return { items: payload, meta: null, links: null };
  }

  return { items: [], meta: null, links: null };
}

/** Extrait un objet resource Laravel (enveloppe data optionnelle). */
export function unwrapResource(data) {
  return data?.data ?? data;
}
