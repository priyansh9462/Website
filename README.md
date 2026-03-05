## GECB Website Monorepo

This repository contains both the **React/Vite frontend** and the **PHP backend** for the GECB website, organized as a simple monorepo.

### Folder structure

- `frontend/` – React + Vite + TypeScript + Tailwind + shadcn-ui SPA  
  - `public/` – static assets served by Vite  
    - `images/` – logos, student photos (create/place images here)  
    - `videos/` – `.mp4` files such as hero/background videos  
  - `src/` – React application source (components, pages, hooks, lib, etc.)  
  - `index.html` – Vite HTML entry  
  - `package.json` – frontend dependencies and scripts  
  - `vite.config.ts`, `tailwind.config.ts`, `tsconfig*.json`, `postcss.config.js`, `eslint.config.js` – tool configuration

- `backend/` – PHP backend (renamed from `college-backend-2`)  
  - `admin/` – admin panel pages  
  - `api/` – API endpoints consumed by the frontend  
  - `config/` – database/auth/jwt/validation configuration  
  - `uploads/` – uploaded files (course materials, gallery, events, etc.)  
  - `index.php` – backend entry point

- `.gitignore` – ignores `node_modules`, build artifacts, logs, editor files globally  
- `README.md` – this project description

### Running the frontend

From the repository root:

```sh
cd frontend
npm install
npm run dev
```

This starts the Vite dev server (port is configured in `frontend/vite.config.ts`).

### Running the backend

The backend is plain PHP. Point your local web server (Apache, Nginx, XAMPP, etc.) at the `backend/` directory so that `backend/index.php` is the document root.

Backend configuration (database, JWT, etc.) is controlled via the files in `backend/config/`. Make sure your PHP environment has access to the correct credentials.

### Environment variables

Frontend environment variables are configured via `frontend/.env` (example in `frontend/.env.example`).  
For example, the Scholr API URL is defined as:

```env
VITE_SCHOLR_API_URL=http://localhost:8080/api/v1
```

Restart the Vite dev server after changing environment variables.
