# ORION Agent (Electron)

Agent desktop Windows/Linux qui envoie CPU, RAM et disque vers ORION Core.

## Prerequis

- Node.js 20+
- ORION Core en marche : `php artisan serve --port=8001`
- `.env` : `ORION_AGENT_BOOTSTRAP_KEY` (meme valeur que dans l'agent)

## Installation

```bash
cd agent
npm install
```

## Lancement dev (navigateur + fenetre Electron)

```bash
npm run electron-dev
```

Demarre en parallele :

- **Navigateur** — agent fonctionnel (register, heartbeat, metriques via API)
- **Fenetre ORION Agent** (Electron) — meme interface + metriques systeme reelles (CPU/RAM)

`npm run dev` = navigateur uniquement.

1. Renseigner l'URL API (`http://localhost:8001/api/v1`)
2. Coller la cle bootstrap (`.env` → `ORION_AGENT_BOOTSTRAP_KEY`)
3. Cliquer **Enregistrer sur ORION**

## Production (local, sans installateur)

```bash
npm run start
```

(`build` React puis `electron .`)

## Generer l'executable Windows (.exe)

```bash
npm install
npm run dist
```

Si erreur `'electron-builder' n'est pas reconnu` : reinstaller les dependances :

```bash
cd agent
npm install
```

En dernier recours (installation corrompue) :

```powershell
Remove-Item -Recurse -Force node_modules
npm install
npm run dist
```

> Besoin d'environ **500 Mo** d'espace disque libre (node_modules + cache Electron).

Fichiers generes dans `agent/release/` :

| Fichier | Description |
|---------|-------------|
| `ORION Agent Setup 1.0.0.exe` | Installateur NSIS (choix du dossier) |
| `ORION Agent 1.0.0.exe` | Version **portable** (sans installation) |

Executable portable uniquement :

```bash
npm run dist:portable
```

> Premier build : telechargement d'Electron (~150 Mo), peut prendre plusieurs minutes.

## Endpoints utilises

- `POST /api/v1/agents/register` (header `X-Orion-Bootstrap-Key`)
- `POST /api/v1/agents/heartbeat` (toutes les 60 s)
- `POST /api/v1/agents/metrics` (batch cpu, ram, ram_total, swap_usage, disk, disk_usage, network_in, network_out, temperature, uptime)
