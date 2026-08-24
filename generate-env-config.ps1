# Script PowerShell de génération de env-config.js
# Pour développement local Windows
#
# Usage :
#   .\generate-env-config.ps1
# Ou avec clés custom :
#   $env:SUPABASE_URL="https://..."; $env:SUPABASE_ANON_KEY="sb_publishable_..."; .\generate-env-config.ps1

Write-Host "🔧 Génération de env-config.js..." -ForegroundColor Cyan

# Valeurs par défaut (projet Le Lézard Bleu)
if (-not $env:SUPABASE_URL) {
    $env:SUPABASE_URL = "https://ztmbqaakfwugizvebscr.supabase.co"
    Write-Host "   ℹ️  SUPABASE_URL non définie, utilisation de la valeur par défaut" -ForegroundColor Yellow
}
if (-not $env:SUPABASE_ANON_KEY) {
    $env:SUPABASE_ANON_KEY = "sb_publishable_4M5nhT9daHi-uf-5WelfUA_o0HE_R6C"
    Write-Host "   ℹ️  SUPABASE_ANON_KEY non définie, utilisation de la valeur par défaut" -ForegroundColor Yellow
}

# Lire le template
$template = Get-Content "dist\env-config.template.js" -Raw

# Remplacer les placeholders
$content = $template `
    -replace '__SUPABASE_URL__', $env:SUPABASE_URL `
    -replace '__SUPABASE_ANON_KEY__', $env:SUPABASE_ANON_KEY

# Écrire le fichier
$content | Out-File "dist\env-config.js" -Encoding UTF8 -NoNewline

Write-Host "✅ env-config.js généré avec succès" -ForegroundColor Green
Write-Host "   SUPABASE_URL: $($env:SUPABASE_URL.Substring(0, [Math]::Min(40, $env:SUPABASE_URL.Length)))..." -ForegroundColor Gray
Write-Host "   SUPABASE_ANON_KEY: $($env:SUPABASE_ANON_KEY.Substring(0, [Math]::Min(30, $env:SUPABASE_ANON_KEY.Length)))..." -ForegroundColor Gray
