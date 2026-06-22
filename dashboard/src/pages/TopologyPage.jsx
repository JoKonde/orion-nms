import { useCallback, useEffect, useRef, useState } from 'react';
import cytoscape from 'cytoscape';
import * as topologyApi from '../api/topology';
import { PageHeader } from '../components/ui/PageHeader';
import { Spinner } from '../components/ui/Spinner';
import { ErrorMessage } from '../components/ui/ErrorMessage';
import { usePermission } from '../hooks/usePermission';
import { useRealtimeRefresh } from '../hooks/useRealtimeRefresh';
import { formatDate } from '../utils/format';

/** Styles Cytoscape — theme dark ORION. */
const CY_STYLE = [
  {
    selector: 'node',
    style: {
      'background-color': '#3b82f6',
      label: 'data(label)',
      color: '#e5e7eb',
      'text-valign': 'bottom',
      'text-halign': 'center',
      'text-margin-y': 6,
      'font-size': 10,
      width: 36,
      height: 36,
      'border-width': 2,
      'border-color': '#1a2234',
    },
  },
  {
    selector: 'node[status="online"]',
    style: { 'background-color': '#22c55e' },
  },
  {
    selector: 'node[status="offline"]',
    style: { 'background-color': '#ef4444' },
  },
  {
    selector: 'node[status="unknown"]',
    style: { 'background-color': '#6b7280' },
  },
  {
    selector: 'node:selected',
    style: {
      'border-color': '#3b82f6',
      'border-width': 3,
    },
  },
  {
    selector: 'edge',
    style: {
      width: 2,
      'line-color': '#374151',
      'target-arrow-color': '#374151',
      'target-arrow-shape': 'triangle',
      'curve-style': 'bezier',
    },
  },
  {
    selector: 'edge[status="up"]',
    style: { 'line-color': '#22c55e', 'target-arrow-color': '#22c55e' },
  },
  {
    selector: 'edge[status="down"]',
    style: { 'line-color': '#ef4444', 'target-arrow-color': '#ef4444' },
  },
  {
    selector: 'edge[status="degraded"]',
    style: { 'line-color': '#f59e0b', 'target-arrow-color': '#f59e0b' },
  },
];

function toElements(graph) {
  const nodes = graph?.elements?.nodes ?? [];
  const edges = graph?.elements?.edges ?? [];
  return [...nodes, ...edges];
}

/** Detruit l'instance Cytoscape si elle existe. */
function destroyCy(cyRef) {
  if (cyRef.current) {
    cyRef.current.destroy();
    cyRef.current = null;
  }
}

/** Layout adapte au nombre de noeuds (cose plante si container 0x0 ou 1 seul noeud). */
function getLayoutOptions(nodeCount) {
  if (nodeCount <= 1) {
    return { name: 'circle', padding: 40, animate: true };
  }
  return { name: 'cose', animate: true, padding: 40 };
}

/** Lance le layout quand le conteneur a des dimensions (evite erreur .w undefined). */
function runLayoutSafe(cy) {
  if (!cy || cy.destroyed()) return;

  const el = cy.container();
  if (!el || el.offsetWidth < 1 || el.offsetHeight < 1) {
    requestAnimationFrame(() => runLayoutSafe(cy));
    return;
  }

  cy.resize();
  const nodeCount = cy.nodes().length;
  if (nodeCount === 0) return;

  cy.layout(getLayoutOptions(nodeCount)).run();
}

/**
 * TopologyPage — cartographie reseau Cytoscape.js (Module 08).
 */
export function TopologyPage() {
  const canManage = usePermission('topology.manage');
  const containerRef = useRef(null);
  const cyRef = useRef(null);

  const [graph, setGraph] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [rebuilding, setRebuilding] = useState(false);
  const [rebuildMsg, setRebuildMsg] = useState('');
  const [selectedNode, setSelectedNode] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await topologyApi.fetchTopology();
      setGraph(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  useRealtimeRefresh(['topology.updated', 'device.discovered'], load);

  const meta = graph?.meta;

  // Recree Cytoscape a chaque changement de graphe (evite etat stale apres rebuild).
  useEffect(() => {
    if (!graph || meta?.node_count === 0) {
      destroyCy(cyRef);
      return;
    }

    if (!containerRef.current) return;

    const elements = toElements(graph);
    destroyCy(cyRef);

    cyRef.current = cytoscape({
      container: containerRef.current,
      elements,
      style: CY_STYLE,
      minZoom: 0.3,
      maxZoom: 3,
    });

    cyRef.current.on('tap', 'node', (evt) => {
      setSelectedNode(evt.target.data());
    });

    cyRef.current.on('tap', (evt) => {
      if (evt.target === cyRef.current) setSelectedNode(null);
    });

    runLayoutSafe(cyRef.current);
  }, [graph, meta?.node_count]);

  useEffect(() => () => destroyCy(cyRef), []);

  const handleRebuild = async () => {
    setRebuilding(true);
    setRebuildMsg('');
    setError(null);
    try {
      const result = await topologyApi.rebuildTopology();
      const removed = result.stats?.stale_subnet_links_removed ?? 0;
      let msg = result.message || 'Topologie reconstruite.';
      if (removed > 0) {
        msg += ` ${removed} ancien(s) lien(s) obsolete(s) supprime(s).`;
      }
      setRebuildMsg(msg);
      if (result.graph) {
        setGraph(result.graph);
        setSelectedNode(null);
      } else {
        await load();
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setRebuilding(false);
    }
  };

  return (
    <div className="page topology-page">
      <PageHeader
        title="Topologie reseau"
        subtitle={
          meta
            ? `${meta.node_count} noeuds · ${meta.edge_count} liens · ${formatDate(meta.generated_at)}`
            : 'Cartographie Cytoscape.js'
        }
        actions={
          canManage && (
            <button
              type="button"
              className="btn btn--primary"
              onClick={handleRebuild}
              disabled={rebuilding}
            >
              {rebuilding ? 'Reconstruction...' : 'Reconstruire'}
            </button>
          )
        }
      />

      {rebuildMsg && <p className="network-msg">{rebuildMsg}</p>}

      {loading && <Spinner label="Chargement de la topologie..." />}
      {error && <ErrorMessage message={error} onRetry={load} />}

      {!loading && !error && (
        <div className="topology-layout">
          <div className="topology-canvas-wrap">
            {meta?.node_count === 0 ? (
              <div className="topology-empty">
                <p>Aucun equipement a afficher.</p>
                <p>Ajoutez des devices ou lancez un scan Nmap en CLI.</p>
              </div>
            ) : (
              <div ref={containerRef} className="topology-canvas" />
            )}
          </div>

          <aside className="topology-sidebar card">
            <h3 className="card__title">Noeud selectionne</h3>
            {selectedNode ? (
              <dl className="detail-list">
                <dt>Nom</dt>
                <dd>{selectedNode.label}</dd>
                <dt>IP</dt>
                <dd>{selectedNode.ip}</dd>
                <dt>Type</dt>
                <dd>{selectedNode.type}</dd>
                <dt>Statut</dt>
                <dd>{selectedNode.status}</dd>
                {selectedNode.vendor && (
                  <>
                    <dt>Constructeur</dt>
                    <dd>{selectedNode.vendor}</dd>
                  </>
                )}
              </dl>
            ) : (
              <p className="card__body">Cliquez sur un noeud du graphe.</p>
            )}
          </aside>
        </div>
      )}
    </div>
  );
}
