#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Audit complet de tous les liens internes du site HTML statique
"""

import re
import os
from pathlib import Path
from collections import defaultdict

# Dossier dist
DIST_DIR = Path(r"c:\Users\User X\OneDrive\Desktop\Projet_Hotel\dist")

# Fichiers HTML existants
html_files = list(DIST_DIR.glob("*.html"))
existing_pages = {f.name for f in html_files}

print("=" * 80)
print("AUDIT COMPLET DES LIENS INTERNES - Site HTML Statique")
print("=" * 80)
print()

print(f"📁 Dossier analysé : {DIST_DIR}")
print(f"📄 Pages HTML trouvées : {len(existing_pages)}")
print()

print("Liste des pages HTML présentes dans dist/ :")
for page in sorted(existing_pages):
    print(f"  ✓ {page}")
print()

# Pattern pour extraire les href
HREF_PATTERN = re.compile(r'href=["\']([^"\']+)["\']', re.IGNORECASE)

# Stockage des liens
all_links = defaultdict(list)
broken_links = []
anchor_links = []
external_links = []

print("=" * 80)
print("ANALYSE DES LIENS INTERNES")
print("=" * 80)
print()

total_internal_links = 0

for html_file in sorted(html_files):
    with open(html_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Extraire tous les href
    matches = HREF_PATTERN.findall(content)
    
    for href in matches:
        # Ignorer les liens externes
        if href.startswith(('http://', 'https://', 'mailto:', 'tel:')):
            external_links.append((html_file.name, href))
            continue
        
        # Ignorer les ancres pures
        if href.startswith('#'):
            anchor_links.append((html_file.name, href))
            continue
        
        # Liens internes (commence par /)
        if href.startswith('/'):
            total_internal_links += 1
            all_links[html_file.name].append(href)
            
            # Extraire le nom du fichier cible
            # Gérer les cas : /index.html, /chambres.html, /index.html#section
            path_only = href.split('#')[0].split('?')[0]
            
            if path_only == '/':
                target_file = 'index.html'
            else:
                target_file = path_only.lstrip('/')
            
            # Vérifier si le fichier existe
            if target_file and target_file not in existing_pages:
                # Vérifier si c'est un dossier assets
                if not target_file.startswith('assets/'):
                    broken_links.append({
                        'source': html_file.name,
                        'href': href,
                        'target': target_file,
                        'reason': 'Fichier cible n\'existe pas'
                    })

print(f"📊 Statistiques :")
print(f"  • Liens internes analysés : {total_internal_links}")
print(f"  • Liens ancres (#) : {len(anchor_links)}")
print(f"  • Liens externes : {len(external_links)}")
print(f"  • Liens cassés détectés : {len(broken_links)}")
print()

if broken_links:
    print("=" * 80)
    print("🚨 LIENS CASSÉS DÉTECTÉS !")
    print("=" * 80)
    print()
    
    for link in broken_links:
        print(f"❌ {link['source']}")
        print(f"   href=\"{link['href']}\"")
        print(f"   → Cible : {link['target']} (INTROUVABLE)")
        print(f"   Raison : {link['reason']}")
        print()
else:
    print("=" * 80)
    print("✅ AUCUN LIEN CASSÉ DÉTECTÉ !")
    print("=" * 80)
    print()

# Détails par page
print("=" * 80)
print("DÉTAILS PAR PAGE")
print("=" * 80)
print()

for page in sorted(all_links.keys()):
    links = all_links[page]
    print(f"📄 {page} ({len(links)} liens internes)")
    
    # Compter les occurrences
    link_counts = defaultdict(int)
    for link in links:
        link_counts[link] += 1
    
    for link, count in sorted(link_counts.items()):
        print(f"   → {link} ({count}×)")
    print()

print("=" * 80)
print("RÉSUMÉ FINAL")
print("=" * 80)
print()
print(f"✓ Pages HTML dans dist/ : {len(existing_pages)}")
print(f"✓ Liens internes analysés : {total_internal_links}")
print(f"✓ Liens cassés : {len(broken_links)}")
print()

if broken_links:
    print("⚠️  STATUT : PROBLÈMES DÉTECTÉS - NE PAS DÉPLOYER")
else:
    print("✅ STATUT : PRÊT POUR PRODUCTION")

print()
