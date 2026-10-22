/**
 * home.js — Page d'accueil
 * Barre de recherche rapide + avis clients.
 */
(function () {
  'use strict';

  const FALLBACK_REVIEWS = [
    {
      guest_name: 'Jean-Paul K.',
      guest_origin: 'Bujumbura, Burundi',
      rating: 5,
      comment:
        "Un cadre idyllique au bord du Lac Tanganyika ! Le service, l'accueil et la cuisine sont irréprochables.",
      stay_type: 'Séjour affaires'
    },
    {
      guest_name: 'Elena & Marc',
      guest_origin: 'Bruxelles, Belgique',
      rating: 5,
      comment:
        'La vue sur le lac au coucher du soleil depuis la Suite Présidentielle est magique. Un séjour inoubliable.',
      stay_type: 'Vacances'
    },
    {
      guest_name: 'Dr Thierry Habimana',
      guest_origin: 'Kigali, Rwanda',
      rating: 5,
      comment:
        'Très impressionné par la qualité du service, la gastronomie et les installations de conférence.',
      stay_type: 'Séminaire'
    }
  ];

  function initHeroSearch() {
    const form = document.getElementById('heroQuickSearchForm');
    if (!form || !window.Forms) return;

    window.Forms.attach(form, {
      onSubmit: function (data) {
        const params = new URLSearchParams();
        if (data.date_arrivee) params.set('date_arrivee', data.date_arrivee);
        if (data.date_depart) params.set('date_depart', data.date_depart);
        if (data.nb_personnes) params.set('nb_personnes', data.nb_personnes);
        window.location.href = '/reservation.html?' + params.toString();
      }
    });
  }

  function renderReviews(reviews) {
    const grid = document.getElementById('reviewsGrid');
    if (!grid) return;
    grid.innerHTML = '';

    reviews.slice(0, 6).forEach((review) => {
      const rating = Math.max(1, Math.min(5, Number(review.rating) || 5));
      const card = document.createElement('article');
      card.className = 'review-card';

      const quote = document.createElement('span');
      quote.className = 'review-quote-icon';
      quote.setAttribute('aria-hidden', 'true');
      quote.textContent = '”';

      const stars = document.createElement('div');
      stars.className = 'review-stars';
      stars.setAttribute('aria-label', `Note : ${rating} sur 5`);
      stars.textContent = '★'.repeat(rating) + '☆'.repeat(5 - rating);

      const comment = document.createElement('p');
      comment.className = 'review-comment';
      comment.textContent = '“' + (review.comment || '') + '”';

      const author = document.createElement('div');
      author.className = 'review-author';

      const avatar = document.createElement('div');
      avatar.className = 'review-avatar';
      avatar.setAttribute('aria-hidden', 'true');
      avatar.textContent = (review.guest_name || 'V').charAt(0).toUpperCase();

      const info = document.createElement('div');
      info.className = 'review-author-info';
      const name = document.createElement('h4');
      name.textContent = review.guest_name || 'Client vérifié';
      const meta = document.createElement('p');
      meta.textContent = [review.stay_type, review.guest_origin].filter(Boolean).join(' · ');
      info.append(name, meta);
      author.append(avatar, info);

      card.append(quote, stars, comment, author);
      grid.appendChild(card);
    });
  }

  async function loadReviews() {
    let reviews = FALLBACK_REVIEWS;
    try {
      const response = await fetch('/api/reviews', {
        headers: { Accept: 'application/json' }
      });
      const contentType = response.headers.get('content-type') || '';
      if (response.ok && contentType.includes('application/json')) {
        const payload = await response.json();
        if (Array.isArray(payload.reviews) && payload.reviews.length) reviews = payload.reviews;
      }
    } catch (_) {
      /* mode démonstration : avis locaux */
    }
    renderReviews(reviews);
  }

  document.addEventListener('DOMContentLoaded', () => {
    initHeroSearch();
    loadReviews();
  });
})();
