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

## Lancement dev

```bash
npm run dev
```

1. Renseigner l'URL API (`http://localhost:8001/api/v1`)
2. Coller la cle bootstrap
3. Cliquer **Enregistrer sur ORION**

Si un equipement existe deja (scan Nmap, meme IP), le backend **met a jour** le device et cree l'agent.

## Production

```bash
npm run build
npm start
```

## Endpoints utilises

- `POST /api/v1/agents/register` (header `X-Orion-Bootstrap-Key`)
- `POST /api/v1/agents/heartbeat` (toutes les 60 s)
- `POST /api/v1/agents/metrics` (batch cpu, ram, disk_usage, uptime)
