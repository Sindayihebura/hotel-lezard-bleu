#!/bin/bash

# Script de génération de env-config.js depuis les variables Netlify
# Ce script est exécuté automatiquement lors du build Netlify

echo "🔧 Génération de env-config.js..."

# Valeurs par défaut si les variables ne sont pas définies dans Netlify
SUPABASE_URL="${SUPABASE_URL:-https://ztmbqaakfwugizvebscr.supabase.co}"
SUPABASE_ANON_KEY="${SUPABASE_ANON_KEY:-sb_publishable_4M5nhT9daHi-uf-5WelfUA_o0HE_R6C}"

if [ -z "$SUPABASE_URL" ] || [ -z "$SUPABASE_ANON_KEY" ]; then
  echo "❌ ERREUR : Les variables SUPABASE_URL et SUPABASE_ANON_KEY sont vides"
  exit 1
fi

# Générer env-config.js depuis le template
sed -e "s|__SUPABASE_URL__|$SUPABASE_URL|g" \
    -e "s|__SUPABASE_ANON_KEY__|$SUPABASE_ANON_KEY|g" \
    dist/env-config.template.js > dist/env-config.js

echo "✅ env-config.js généré avec succès"
echo "   SUPABASE_URL: ${SUPABASE_URL:0:40}..."
echo "   SUPABASE_ANON_KEY: ${SUPABASE_ANON_KEY:0:30}..."
