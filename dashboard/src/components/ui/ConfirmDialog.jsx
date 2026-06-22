import { Modal } from './Modal';

/** ConfirmDialog — confirmation suppression ou action critique. */
export function ConfirmDialog({ open, title, message, confirmLabel = 'Confirmer', onConfirm, onClose, danger = false }) {
  return (
    <Modal open={open} title={title} onClose={onClose}>
      <p className="confirm-dialog__message">{message}</p>
      <div className="confirm-dialog__actions">
        <button type="button" className="btn btn--secondary" onClick={onClose}>
          Annuler
        </button>
        <button
          type="button"
          className={`btn ${danger ? 'btn--danger' : 'btn--primary'}`}
          onClick={onConfirm}
        >
          {confirmLabel}
        </button>
      </div>
    </Modal>
  );
}
