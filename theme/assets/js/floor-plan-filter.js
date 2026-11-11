(() => {
	'use strict';

	const debounce = (fn, delay = 250) => {
		let timeoutId = null;
		return (...args) => {
			window.clearTimeout(timeoutId);
			timeoutId = window.setTimeout(() => fn(...args), delay);
		};
	};

	class FloorPlanFilterController {
		constructor(rootElement) {
			this.root = rootElement;
			this.endpoint =
				window.FreyFloorPlanConfig?.restUrl ||
				this.root.dataset.fpEndpoint ||
				'/wp-json/theme/v1/floor-plans';

			this.form = this.root.querySelector('#fp-filter-form');
			this.pillButtons = Array.from(this.root.querySelectorAll('.fp-pill'));
			this.maxPriceInput = this.root.querySelector('#fp-max-price');
			this.moveInDateInput = this.root.querySelector('#fp-move-in-date');
			this.clearInlineBtn = this.root.querySelector('#fp-clear-inline');
			this.resetEmptyBtn = this.root.querySelector('#fp-reset-btn');
			this.liveRegion = this.root.querySelector('#fp-live-region');
			this.loadingState = this.root.querySelector('#fp-loading-state');
			this.resultsGrid = this.root.querySelector('#fp-results-grid');
			this.emptyState = this.root.querySelector('#fp-empty-state');

			this.state = {
				bedrooms: new Set(),
				maxPrice: '',
				moveInDate: '',
			};

			this.abortController = null;
			this.init();
		}

		init() {
			this.hydrateStateFromUrl();
			this.syncUiToState();
			this.bindEvents();
			this.fetchFloorPlans();
		}

		hydrateStateFromUrl() {
			const params = new URLSearchParams(window.location.search);

			if (params.has('bedrooms')) {
				const rawBeds = params.get('bedrooms') || '';
				rawBeds.split(',').forEach((val) => {
					const trimmed = val.trim();
					if (trimmed !== '' && /^\d+$/.test(trimmed)) {
						this.state.bedrooms.add(Number.parseInt(trimmed, 10));
					}
				});
			}

			if (params.has('max_price')) {
				const rawPrice = (params.get('max_price') || '').trim();
				if (/^\d+$/.test(rawPrice) && Number.parseInt(rawPrice, 10) > 0) {
					this.state.maxPrice = rawPrice;
				}
			}

			if (params.has('move_in_date')) {
				const rawDate = (params.get('move_in_date') || '').trim();
				if (/^\d{4}-\d{2}-\d{2}$/.test(rawDate)) {
					this.state.moveInDate = rawDate;
				}
			}
		}

		syncUiToState() {
			this.pillButtons.forEach((btn) => {
				const bedVal = Number.parseInt(btn.dataset.bedValue, 10);
				const isSelected = this.state.bedrooms.has(bedVal);
				btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
				btn.classList.toggle('is-active', isSelected);
			});

			if (this.maxPriceInput) {
				this.maxPriceInput.value = this.state.maxPrice;
			}

			if (this.moveInDateInput) {
				this.moveInDateInput.value = this.state.moveInDate;
			}

			this.updateClearButtonVisibility();
		}

		syncStateToUrl() {
			const params = new URLSearchParams();

			if (this.state.bedrooms.size > 0) {
				const sortedBeds = Array.from(this.state.bedrooms).sort((a, b) => a - b);
				params.set('bedrooms', sortedBeds.join(','));
			}

			if (this.state.maxPrice !== '' && Number.parseInt(this.state.maxPrice, 10) > 0) {
				params.set('max_price', this.state.maxPrice);
			}

			if (this.state.moveInDate !== '') {
				params.set('move_in_date', this.state.moveInDate);
			}

			const queryString = params.toString();
			const newUrl = queryString
				? `${window.location.pathname}?${queryString}`
				: window.location.pathname;

			window.history.replaceState({ path: newUrl }, '', newUrl);
			this.updateClearButtonVisibility();
		}

		updateClearButtonVisibility() {
			if (!this.clearInlineBtn) return;
			const hasActiveFilters =
				this.state.bedrooms.size > 0 ||
				this.state.maxPrice !== '' ||
				this.state.moveInDate !== '';
			this.clearInlineBtn.hidden = !hasActiveFilters;
		}

		bindEvents() {
			if (this.form) {
				this.form.addEventListener('submit', (e) => e.preventDefault());
			}

			this.pillButtons.forEach((btn) => {
				btn.addEventListener('click', () => {
					const bedVal = Number.parseInt(btn.dataset.bedValue, 10);
					if (Number.isNaN(bedVal)) return;

					if (this.state.bedrooms.has(bedVal)) {
						this.state.bedrooms.delete(bedVal);
					} else {
						this.state.bedrooms.add(bedVal);
					}

					this.syncUiToState();
					this.syncStateToUrl();
					this.fetchFloorPlans();
				});
			});

			if (this.maxPriceInput) {
				const handlePriceInput = debounce((event) => {
					const raw = event.target.value.trim();
					const parsed = Number.parseInt(raw, 10);
					this.state.maxPrice =
						raw !== '' && !Number.isNaN(parsed) && parsed > 0
							? String(parsed)
							: '';
					this.syncStateToUrl();
					this.fetchFloorPlans();
				}, 250);

				this.maxPriceInput.addEventListener('input', handlePriceInput);
			}

			if (this.moveInDateInput) {
				this.moveInDateInput.addEventListener('change', (event) => {
					const val = event.target.value.trim();
					this.state.moveInDate = /^\d{4}-\d{2}-\d{2}$/.test(val) ? val : '';
					this.syncStateToUrl();
					this.fetchFloorPlans();
				});
			}

			const handleReset = () => {
				this.state.bedrooms.clear();
				this.state.maxPrice = '';
				this.state.moveInDate = '';
				this.syncUiToState();
				this.syncStateToUrl();
				this.fetchFloorPlans();
			};

			if (this.resetEmptyBtn) {
				this.resetEmptyBtn.addEventListener('click', handleReset);
			}
			if (this.clearInlineBtn) {
				this.clearInlineBtn.addEventListener('click', handleReset);
			}
		}

		async fetchFloorPlans() {
			if (this.abortController) {
				this.abortController.abort();
			}
			this.abortController = new AbortController();

			this.setLoading(true);

			const requestUrl = new URL(this.endpoint, window.location.origin);

			if (this.state.bedrooms.size > 0) {
				const sortedBeds = Array.from(this.state.bedrooms).sort((a, b) => a - b);
				requestUrl.searchParams.set('bedrooms', sortedBeds.join(','));
			}

			if (this.state.maxPrice !== '') {
				requestUrl.searchParams.set('max_price', this.state.maxPrice);
			}

			if (this.state.moveInDate !== '') {
				requestUrl.searchParams.set('move_in_date', this.state.moveInDate);
			}

			try {
				const response = await fetch(requestUrl.toString(), {
					method: 'GET',
					headers: {
						Accept: 'application/json',
					},
					signal: this.abortController.signal,
				});

				if (!response.ok) {
					throw new Error(`HTTP error: ${response.status}`);
				}

				const data = await response.json();
				this.renderResults(data);
			} catch (error) {
				if (error.name === 'AbortError') {
					return;
				}
				this.renderError();
			} finally {
				this.setLoading(false);
			}
		}

		setLoading(isLoading) {
			this.resultsGrid.setAttribute('aria-busy', isLoading ? 'true' : 'false');
			this.loadingState.hidden = !isLoading;

			if (isLoading) {
				this.emptyState.hidden = true;
			}
		}

		renderResults(data) {
			const items = Array.isArray(data?.items) ? data.items : [];
			const total = typeof data?.total === 'number' ? data.total : items.length;

			this.resultsGrid.replaceChildren();

			const countAnnouncement =
				total === 1
					? 'Showing 1 available floor plan.'
					: `Showing ${total} available floor plans.`;
			this.liveRegion.textContent = countAnnouncement;

			if (items.length === 0) {
				this.resultsGrid.hidden = true;
				this.emptyState.hidden = false;
				return;
			}

			this.emptyState.hidden = true;
			this.resultsGrid.hidden = false;

			const fragment = document.createDocumentFragment();

			items.forEach((item) => {
				fragment.appendChild(this.createCardNode(item));
			});

			this.resultsGrid.appendChild(fragment);
		}

		createCardNode(item) {
			const article = document.createElement('article');
			article.className = 'fp-card';

			const cardHeader = document.createElement('div');
			cardHeader.className = 'fp-card__header';

			const badge = document.createElement('span');
			badge.className =
				item.bedrooms === 0 ? 'fp-badge fp-badge--studio' : 'fp-badge';
			badge.textContent = item.bedroom_label;

			const availability = document.createElement('span');
			availability.className = 'fp-availability';
			availability.textContent = item.formatted_available;

			cardHeader.append(badge, availability);

			const title = document.createElement('h2');
			title.className = 'fp-card__title';
			title.textContent = item.title;

			const specsList = document.createElement('dl');
			specsList.className = 'fp-card__specs';

			const addSpec = (label, value) => {
				const div = document.createElement('div');
				div.className = 'fp-spec';
				const dt = document.createElement('dt');
				dt.textContent = label;
				const dd = document.createElement('dd');
				dd.textContent = value;
				div.append(dt, dd);
				specsList.appendChild(div);
			};

			addSpec('Starting At', `${item.formatted_price}/mo`);
			addSpec('Bathrooms', `${item.bathrooms} Bath`);
			addSpec('Area', `${item.sqft} sq ft`);

			const cta = document.createElement('a');
			cta.className = 'fp-card__cta';
			cta.href = item.permalink || '#';
			cta.textContent = 'View Residence Details';
			cta.setAttribute(
				'aria-label',
				`View details for ${item.title}, ${item.bedroom_label}, starting at ${item.formatted_price} per month`
			);

			article.append(cardHeader, title, specsList, cta);
			return article;
		}

		renderError() {
			this.resultsGrid.replaceChildren();
			this.liveRegion.textContent =
				'Unable to load floor plans right now. Please try again.';
			this.emptyState.hidden = false;
		}
	}

	document.addEventListener('DOMContentLoaded', () => {
		const moduleRoot = document.querySelector('.fp-module');
		if (moduleRoot) {
			new FloorPlanFilterController(moduleRoot);
		}
	});
})();
