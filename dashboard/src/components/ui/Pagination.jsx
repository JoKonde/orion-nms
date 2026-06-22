/** Pagination — navigation pages Laravel meta. */
export function Pagination({ meta, onPageChange }) {
  if (!meta || meta.last_page <= 1) return null;

  return (
    <div className="pagination">
      <button
        type="button"
        className="btn btn--secondary"
        disabled={meta.current_page <= 1}
        onClick={() => onPageChange(meta.current_page - 1)}
      >
        Precedent
      </button>
      <span className="pagination__info">
        Page {meta.current_page} / {meta.last_page} ({meta.total} total)
      </span>
      <button
        type="button"
        className="btn btn--secondary"
        disabled={meta.current_page >= meta.last_page}
        onClick={() => onPageChange(meta.current_page + 1)}
      >
        Suivant
      </button>
    </div>
  );
}
