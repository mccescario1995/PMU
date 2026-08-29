Run the PMU API server (`php artisan serve` in `PMUAPI`) and PMU UI dev server (`npm run dev` in `PMUUI`) simultaneously.

Use PowerShell to start both as background processes without blocking:

```powershell
Start-Process -FilePath "php" -ArgumentList "artisan serve" -WorkingDirectory "PMUAPI" -NoNewWindow
Start-Process -FilePath "npm" -ArgumentList "run dev" -WorkingDirectory "PMUUI" -NoNewWindow
```

After starting both, confirm they are running and report the URLs/ports they are listening on.
