console.log('Dashboard JS loaded');

let draggedWidgetType = null;
const MAX_WIDGETS = 10;

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready');
    initDragAndDrop();
    loadExistingWidgets();
    initRemoveButtons();
    initEditButtons();
});

// ========================
// DRAG & DROP
// ========================

function initDragAndDrop() {
    const draggables = document.querySelectorAll('[draggable="true"]');
    const grid = document.getElementById('dashboard-grid');

    if (!grid) {
        console.error('Grid not found!');
        return;
    }

    draggables.forEach(function(draggable) {
        draggable.addEventListener('dragstart', function(e) {
            // Vérifier la limite
            const widgetCount = document.querySelectorAll('.widget-container').length;
            if (widgetCount >= MAX_WIDGETS) {
                e.preventDefault();
                showLimitDialog();
                return;
            }

            draggedWidgetType = e.target.dataset.widgetType;
            e.target.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('text/plain', draggedWidgetType);
        });

        draggable.addEventListener('dragend', function(e) {
            e.target.style.opacity = '1';
        });
    });

    grid.addEventListener('dragenter', function(e) {
        e.preventDefault();
        grid.classList.add('bg-blue-100', 'border-blue-500');
    });

    grid.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    grid.addEventListener('dragleave', function(e) {
        if (e.target === grid) {
            grid.classList.remove('bg-blue-100', 'border-blue-500');
        }
    });

    grid.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        grid.classList.remove('bg-blue-100', 'border-blue-500');

        if (!draggedWidgetType) {
            return;
        }

        // Trouver la prochaine position disponible
        const existingWidgets = document.querySelectorAll('.widget-container');
        const positions = new Set();
        
        existingWidgets.forEach(function(w) {
            const row = parseInt(w.dataset.row);
            const col = parseInt(w.dataset.column);
            positions.add(row + '-' + col);
        });

        let row = 1, column = 1;
        let found = false;
        
        for (let r = 1; r <= 10; r++) {
            for (let c = 1; c <= 2; c++) {
                if (!positions.has(r + '-' + c)) {
                    row = r;
                    column = c;
                    found = true;
                    break;
                }
            }
            if (found) break;
        }

        openWidgetConfigModal(draggedWidgetType, row, column);
        draggedWidgetType = null;
    });
}

function showLimitDialog() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md mx-4">
            <h2 class="text-2xl font-bold mb-4 text-red-600">Limite atteinte ! </h2>
            <p class="mb-6">Vous avez atteint la limite de <strong>10 widgets</strong> autorisés pour cette version de démonstration.</p>
            <p class="mb-6">Pour ajouter de nouveaux widgets, veuillez supprimer un widget existant ou passer à la version complète.</p>
            <button class="w-full bg-primary text-white px-4 py-2 rounded-md hover:bg-primary/90" onclick="this.closest('.fixed').remove()">
                Compris
            </button>
        </div>
    `;
    document.body.appendChild(modal);
}
// ========================
// MODALS DE CONFIGURATION
// ========================

function openWidgetConfigModal(type, row, column, existingConfig = null, widgetId = null) {
    const modalHTML = generateConfigModal(type, row, column, existingConfig);
    
    const modalContainer = document.createElement('div');
    modalContainer.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modalContainer.innerHTML = modalHTML;
    document.body.appendChild(modalContainer);

    modalContainer.querySelector('.cancel-btn').addEventListener('click', function() {
        document.body.removeChild(modalContainer);
    });

    modalContainer.querySelector('.save-btn').addEventListener('click', function() {
        if (widgetId) {
            updateWidget(widgetId, modalContainer);
        } else {
            saveWidget(type, row, column, modalContainer);
        }
    });

    if (['product_search', 'sugar_salt_comparison', 'nutriscore_comparison', 'nova_comparison', 'nutrition_pie'].indexOf(type) !== -1) {
        initProductSearch(modalContainer, type, existingConfig);
    }
}

function generateConfigModal(type, row, column, existingConfig) {
    const titles = {
        'product_search': 'Recherche Produit',
        'sugar_salt_comparison': 'Comparaison Sucre et Sel',
        'nutriscore_comparison': 'Comparaison Nutriscore',
        'nova_comparison': 'Comparaison NOVA',
        'nutrition_pie': 'Graphique Nutritionnel'
    };

    const needsMultiple = ['sugar_salt_comparison', 'nutriscore_comparison', 'nova_comparison'].indexOf(type) !== -1;
    const needsSingle = ['product_search', 'nutrition_pie'].indexOf(type) !== -1;

    let selectedBarcode = '';
    let selectedBarcodes = [];

    if (existingConfig) {
        if (existingConfig.barcode) selectedBarcode = existingConfig.barcode;
        if (existingConfig.barcodes) selectedBarcodes = existingConfig.barcodes;
    }

    return `
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold mb-4">${titles[type]}</h2>
            
            ${needsSingle ? `
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Rechercher un produit</label>
                    <input type="text" 
                           class="product-search-input w-full px-3 py-2 border rounded-md" 
                           placeholder="Tapez le nom du produit..."
                           data-single="true">
                    <div class="product-search-results mt-2"></div>
                    <input type="hidden" class="selected-barcode" value="${selectedBarcode}">
                    ${selectedBarcode ? '<div class="mt-2 text-sm text-green-600">✓ Produit sélectionné</div>' : ''}
                </div>
            ` : ''}

            ${needsMultiple ? `
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Ajouter des produits (1 à 5)</label>
                    <input type="text" 
                           class="product-search-input w-full px-3 py-2 border rounded-md" 
                           placeholder="Tapez le nom du produit..."
                           data-multiple="true">
                    <div class="product-search-results mt-2"></div>
                    
                    <div class="selected-products mt-4 space-y-2"></div>
                    <input type="hidden" class="selected-barcodes" value='${JSON.stringify(selectedBarcodes)}'>
                </div>
            ` : ''}

            <div class="flex gap-2 mt-6">
                <button class="save-btn flex-1 bg-primary text-white px-4 py-2 rounded-md hover:bg-primary/90 flex items-center justify-center">
                    <span class="save-btn-text">Ajouter au dashboard</span>
                    <span class="save-btn-loader hidden ml-2">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
                <button class="cancel-btn flex-1 bg-gray-200 px-4 py-2 rounded-md hover:bg-gray-300">
                    Annuler
                </button>
            </div>
        </div>
    `;
}

// ========================
// RECHERCHE PRODUITS
// ========================

let currentSearchRequest = null;

function initProductSearch(container, widgetType, existingConfig) {
    const input = container.querySelector('.product-search-input');
    const resultsDiv = container.querySelector('.product-search-results');
    const isSingle = input.dataset.single === 'true';
    const isMultiple = input.dataset.multiple === 'true';

    let searchTimeout;

    // Pré-remplir si config existante
    if (existingConfig && existingConfig.barcodes) {
        existingConfig.barcodes.forEach(function(barcode) {
            // TODO: Charger les infos produit et afficher
        });
    }

    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        // Annuler la requête précédente
        if (currentSearchRequest) {
            currentSearchRequest = null;
        }

        const query = this.value.trim();

        // minimum 2 caractères 
        if (query.length < 2) {
            resultsDiv.innerHTML = '';
            return;
        }

        // Afficher loader
        resultsDiv.innerHTML = '<div class="flex items-center p-2"><div class="animate-spin rounded-full h-5 w-5 border-b-2 border-primary mr-2"></div><span class="text-sm">Recherche... (~ 6 secondes)</span></div>';

        searchTimeout = setTimeout(function() {
            const thisRequest = {};
            currentSearchRequest = thisRequest;

            fetch('/dashboard/search-products?q=' + encodeURIComponent(query))
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    // Ignorer si ce n'est plus la requête courante
                    if (currentSearchRequest !== thisRequest) {
                        console.log('Requête obsolète ignorée');
                        return;
                    }
                    displaySearchResults(data.products, resultsDiv, container, isSingle, isMultiple);
                })
                .catch(function(err) {
                    if (currentSearchRequest === thisRequest) {
                        console.error('Search error:', err);
                        resultsDiv.innerHTML = '<p class="text-red-500 text-sm">❌ Erreur de recherche</p>';
                    }
                });
        }, 500);
    });
}

function displaySearchResults(products, resultsDiv, container, isSingle, isMultiple) {
    if (products.length === 0) {
        resultsDiv.innerHTML = '<p class="text-gray-500 text-sm">Aucun produit trouvé</p>';
        return;
    }

    resultsDiv.innerHTML = products.map(function(p) {
        return `
            <div class="product-result p-2 bg-gray-50 rounded cursor-pointer hover:bg-gray-100 mb-1"
                 data-barcode="${p.barcode}"
                 data-name="${p.name}"
                 data-brands="${p.brands}">
                <div class="font-medium">${p.name}</div>
                <div class="text-sm text-gray-600">${p.brands} • Code: ${p.barcode}</div>
                ${p.nutriscore !== 'N/A' ? '<span class="text-xs bg-green-100 px-2 py-1 rounded">Nutriscore ' + p.nutriscore + '</span>' : ''}
            </div>
        `;
    }).join('');

    resultsDiv.querySelectorAll('.product-result').forEach(function(result) {
        result.addEventListener('click', function() {
            const barcode = this.dataset.barcode;
            const name = this.dataset.name;
            const brands = this.dataset.brands;

            if (isSingle) {
                container.querySelector('.selected-barcode').value = barcode;
                resultsDiv.innerHTML = '<div class="bg-green-50 p-2 rounded text-sm">✓ ' + name + ' sélectionné</div>';
            } else if (isMultiple) {
                addProductToSelection(container, barcode, name, brands);
                resultsDiv.innerHTML = '';
                container.querySelector('.product-search-input').value = '';
            }
        });
    });
}
function addProductToSelection(container, barcode, name, brands) {
    const selectedDiv = container.querySelector('.selected-products');
    const barcodesInput = container.querySelector('.selected-barcodes');
    
    let barcodes = JSON.parse(barcodesInput.value || '[]');

    if (barcodes.indexOf(barcode) !== -1) {
        alert('Ce produit est déjà ajouté');
        return;
    }

    if (barcodes.length >= 5) {
        alert('Maximum 5 produits');
        return;
    }

    barcodes.push(barcode);
    barcodesInput.value = JSON.stringify(barcodes);

    const productCard = document.createElement('div');
    productCard.className = 'flex items-center justify-between bg-blue-50 p-2 rounded';
    productCard.innerHTML = `
        <div class="flex-1">
            <div class="font-medium text-sm">${name}</div>
            <div class="text-xs text-gray-600">${brands}</div>
        </div>
        <button class="remove-product text-red-500 hover:text-red-700 font-bold" data-barcode="${barcode}">×</button>
    `;

    selectedDiv.appendChild(productCard);

    productCard.querySelector('.remove-product').addEventListener('click', function() {
        const idx = barcodes.indexOf(barcode);
        if (idx > -1) barcodes.splice(idx, 1);
        barcodesInput.value = JSON.stringify(barcodes);
        productCard.remove();
    });
}

// ========================
// SAUVEGARDER WIDGET
// ========================

function saveWidget(type, row, column, modalContainer) {
    let configuration = {};

    if (type === 'product_search' || type === 'nutrition_pie') {
        const barcode = modalContainer.querySelector('.selected-barcode').value;
        if (!barcode) {
            alert('Veuillez sélectionner un produit');
            return;
        }
        configuration = { barcode: barcode };
    } else if (['sugar_salt_comparison', 'nutriscore_comparison', 'nova_comparison'].indexOf(type) !== -1) {
        const barcodes = JSON.parse(modalContainer.querySelector('.selected-barcodes').value || '[]');
        if (barcodes.length === 0) {
            alert('Veuillez ajouter au moins un produit');
            return;
        }
        configuration = { barcodes: barcodes };
    }

    // Afficher loader
    const saveBtn = modalContainer.querySelector('.save-btn');
    const saveBtnText = saveBtn.querySelector('.save-btn-text');
    const saveBtnLoader = saveBtn.querySelector('.save-btn-loader');
    
    saveBtn.disabled = true;
    saveBtnText.textContent = 'Ajout en cours...';
    saveBtnLoader.classList.remove('hidden');

    fetch('/dashboard/widget/add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: type, row: row, column: column, configuration: configuration })
    })
    .then(function(res) { 
        if (res.status === 500) {
            return res.text().then(function(text) {
                throw new Error('Erreur serveur: ' + text.substring(0, 200));
            });
        }
        return res.json(); 
    })
    .then(function(data) {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Erreur inconnue'));
            saveBtn.disabled = false;
            saveBtnText.textContent = 'Ajouter au dashboard';
            saveBtnLoader.classList.add('hidden');
        }
    })
    .catch(function(err) {
        console.error('Fetch error:', err);
        alert('Erreur: ' + err.message);
        saveBtn.disabled = false;
        saveBtnText.textContent = 'Ajouter au dashboard';
        saveBtnLoader.classList.add('hidden');
    });
}

// ========================
// MODIFIER WIDGET
// ========================

function updateWidget(widgetId, modalContainer) {
    const configuration = {};

    const barcodeInput = modalContainer.querySelector('.selected-barcode');
    const barcodesInput = modalContainer.querySelector('.selected-barcodes');

    if (barcodeInput) {
        const barcode = barcodeInput.value;
        if (!barcode) {
            alert('Veuillez sélectionner un produit');
            return;
        }
        configuration.barcode = barcode;
    } else if (barcodesInput) {
        const barcodes = JSON.parse(barcodesInput.value || '[]');
        if (barcodes.length === 0) {
            alert('Veuillez ajouter au moins un produit');
            return;
        }
        configuration.barcodes = barcodes;
    }

    // Afficher loader
    const saveBtn = modalContainer.querySelector('.save-btn');
    const saveBtnText = saveBtn.querySelector('.save-btn-text');
    const saveBtnLoader = saveBtn.querySelector('.save-btn-loader');
    
    saveBtn.disabled = true;
    saveBtnText.textContent = 'Mise à jour...';
    saveBtnLoader.classList.remove('hidden');

    fetch('/dashboard/widget/' + widgetId + '/update-config', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ configuration: configuration })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + data.error);
            saveBtn.disabled = false;
            saveBtnText.textContent = 'Mettre à jour';
            saveBtnLoader.classList.add('hidden');
        }
    })
    .catch(function(err) {
        alert('Erreur: ' + err.message);
        saveBtn.disabled = false;
        saveBtnText.textContent = 'Mettre à jour';
        saveBtnLoader.classList.add('hidden');
    });
}

// ========================
// CHARGER WIDGETS EXISTANTS
// ========================

function loadExistingWidgets() {
    document.querySelectorAll('.widget-container').forEach(function(widget) {
        const widgetId = widget.dataset.widgetId;
        const widgetType = widget.dataset.widgetType;
        
        loadWidgetData(widgetId, widgetType);
    });
}

function loadWidgetData(widgetId, widgetType) {
    const contentDiv = document.getElementById('widget-content-' + widgetId);

    fetch('/dashboard/widget/' + widgetId + '/data')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.error) {
                contentDiv.innerHTML = '<p class="text-red-500 text-center">' + data.error + '</p>';
                return;
            }

            renderWidgetContent(widgetId, widgetType, data, contentDiv);
        })
        .catch(function(err) {
            console.error('Error loading widget:', err);
            contentDiv.innerHTML = '<p class="text-red-500 text-center">Erreur de chargement</p>';
        });
}

function renderWidgetContent(widgetId, widgetType, data, contentDiv) {
    if (data.type === 'product_info') {
        renderProductInfo(data.product, contentDiv);
    } else if (data.type === 'bar') {
        renderBarChart(widgetId, data.data, contentDiv);
    } else if (data.type === 'nutriscore_comparison') {
        renderNutriscoreComparison(data.products, contentDiv);
    } else if (data.type === 'nova_comparison') {
        renderNovaComparison(data.products, contentDiv);
    } else if (data.type === 'pie') {
        renderPieChart(widgetId, data, contentDiv);
    }
}

function renderProductInfo(product, container) {
    const nutriscoreColors = {
        'A': '#038141',
        'B': '#85BB2F',
        'C': '#FECB02',
        'D': '#EE8100',
        'E': '#E63E11',
        'N/A': '#CCCCCC'
    };

    const novaColors = {
        '1': '#4CAF50',
        '2': '#FFC107',
        '3': '#FF9800',
        '4': '#F44336',
        'N/A': '#CCCCCC'
    };

    const nutriscoreColor = nutriscoreColors[product.nutriscore] || nutriscoreColors['N/A'];
    const novaColor = novaColors[product.nova] || novaColors['N/A'];

    container.innerHTML = `
        <div class="space-y-2 text-sm">
            <div class="font-bold text-lg mb-3" style="width: 300px; word-wrap: break-word;">${product.name}</div>
            
            <div class="text-gray-600 mb-3" style="width: 300px;">
                <strong>Marque :</strong> ${product.brands}
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <div class="text-xs text-gray-500 mb-1">Code-barres</div>
                    <div class="font-mono text-xs">${product.barcode}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 mb-1">Quantité</div>
                    <div>${product.quantity}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-3">
                <div>
                    <div class="text-xs text-gray-500 mb-1">Nutriscore</div>
                    <span class="inline-block px-3 py-1 rounded-full text-white font-bold text-lg" 
                          style="background-color: ${nutriscoreColor};">
                        ${product.nutriscore}
                    </span>
                </div>
                <div>
                    <div class="text-xs text-gray-500 mb-1">NOVA</div>
                    <span class="inline-block px-3 py-1 rounded-full text-white font-bold text-lg" 
                          style="background-color: ${novaColor};">
                        ${product.nova}
                    </span>
                </div>
            </div>

            <div class="mt-3" style="max-width: 300px;">
                <div class="text-xs text-gray-500 mb-1">Catégories</div>
                <div class="text-xs">${product.categories}</div>
            </div>

            <div class="mt-2" style="max-width: 300px;">
                <div class="text-xs text-gray-500 mb-1">Origine</div>
                <div class="text-xs">${product.origins}</div>
            </div>

            <a href="${product.url}" 
               target="_blank" 
               class="block mt-3 text-center bg-blue-500 text-white px-3 py-2 rounded hover:bg-blue-600"
               style="width: 125px;">
                Voir sur OFF →
            </a>
        </div>
    `;
}

function renderBarChart(widgetId, chartData, container) {
    container.innerHTML = '<canvas id="chart-' + widgetId + '" class="w-full" height="200"></canvas>';
    
    new Chart(document.getElementById('chart-' + widgetId), {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
}

function renderNutriscoreComparison(products, container) {
    const nutriscoreColors = {
        'A': '#038141',
        'B': '#85BB2F',
        'C': '#FECB02',
        'D': '#EE8100',
        'E': '#E63E11',
        'N/A': '#CCCCCC'
    };

    container.innerHTML = '<div class="space-y-3">' +
        products.map(function(p) {
            const color = nutriscoreColors[p.nutriscore] || nutriscoreColors['N/A'];
            return `
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <div class="flex-1" style="max-width: 300px;">
                        <div class="font-medium text-sm">${p.name}</div>
                        <div class="text-xs text-gray-600"><strong>Marque :</strong> ${p.brands}</div>
                        <div class="text-xs text-gray-400">${p.barcode}</div>
                    </div>
                    <div>
                        <span class="inline-block w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-xl"
                              style="background-color: ${color}">
                            ${p.nutriscore}
                        </span>
                    </div>
                </div>
            `;
        }).join('') +
        '</div>';
}

function renderNovaComparison(products, container) {
    const novaColors = {
        '1': '#4CAF50',
        '2': '#FFC107',
        '3': '#FF9800',
        '4': '#F44336',
        'N/A': '#CCCCCC'
    };

    container.innerHTML = '<div class="space-y-3">' +
        products.map(function(p) {
            const color = novaColors[p.nova] || novaColors['N/A'];
            return `
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <div class="flex-1" style="max-width: 300px;">
                        <div class="font-medium text-sm">${p.name}</div>
                        <div class="text-xs text-gray-600"><strong>Marque :</strong> ${p.brands}</div>
                        <div class="text-xs text-gray-400">${p.barcode}</div>
                    </div>
                    <div>
                        <span class="inline-block w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-xl"
                              style="background-color: ${color}">
                            ${p.nova}
                        </span>
                    </div>
                </div>
            `;
        }).join('') +
        '</div>';
}

function renderPieChart(widgetId, data, container) {
    container.innerHTML = `
        <div class="text-center mb-2 font-semibold">${data.product_name}</div>
        <canvas id="chart-${widgetId}" class="w-full" height="250"></canvas>
    `;
    
    new Chart(document.getElementById('chart-' + widgetId), {
        type: 'pie',
        data: data.data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// ========================
// SUPPRIMER WIDGETS
// ========================

function initRemoveButtons() {
    document.querySelectorAll('.remove-widget').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Supprimer ce widget ?')) return;

            const widgetId = this.dataset.widgetId;

            fetch('/dashboard/widget/' + widgetId + '/remove', {
                method: 'DELETE'
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.error);
                }
            });
        });
    });
}

// ========================
// MODIFIER WIDGETS
// ========================

function initEditButtons() {
    document.querySelectorAll('.edit-widget').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const widgetId = this.dataset.widgetId;
            const widgetType = this.dataset.widgetType;
            const configuration = JSON.parse(this.dataset.configuration || '{}');

            openWidgetConfigModal(widgetType, null, null, configuration, widgetId);
        });
    });
}
