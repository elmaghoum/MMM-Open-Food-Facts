console.log('🚀 Dashboard JS loaded');

let draggedWidgetType = null;
let currentSearchRequest = null;
const MAX_WIDGETS = 10;

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM ready');
    initDragAndDrop();
    loadExistingWidgets();
    initRemoveButtons();
    initEditButtons();
});

// ========================
// TOAST NOTIFICATIONS (Shadcn style)
// ========================

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    
    const icons = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>',
        error: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>'
    };
    
    const colors = {
        success: 'bg-green-50 border-green-200 text-green-800',
        error: 'bg-red-50 border-red-200 text-red-800',
        warning: 'bg-yellow-50 border-yellow-200 text-yellow-800'
    };
    
    toast.className = 'fixed top-4 right-4 z-50 rounded-lg border p-4 shadow-lg transition-all duration-300 max-w-md ' + colors[type];
    toast.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">${icons[type]}</div>
            <div class="flex-1">
                <p class="text-sm font-medium">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 opacity-70 hover:opacity-100">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(function() {
            if (toast.parentElement) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 4000);
}

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
            console.log('Drag started:', draggedWidgetType);
        });

        draggable.addEventListener('dragend', function(e) {
            e.target.style.opacity = '1';
        });
    });

    grid.addEventListener('dragenter', function(e) {
        e.preventDefault();
        grid.style.borderColor = 'hsl(var(--primary))';
    });

    grid.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    grid.addEventListener('dragleave', function(e) {
        if (e.target === grid) {
            grid.style.borderColor = '';
        }
    });

    grid.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        grid.style.borderColor = '';

        if (!draggedWidgetType) {
            return;
        }

        console.log('Drop detected:', draggedWidgetType);

        if (draggedWidgetType === 'shopping_list') {
            const existingShoppingList = document.querySelector('.widget-content[data-widget-type="shopping_list"]');
            if (existingShoppingList) {
                showLimitDialog('Vous avez déjà une liste de course. Vous ne pouvez en avoir qu\'une seule.');
                draggedWidgetType = null;
                return;
            }
        }

        const existingWidgets = document.querySelectorAll('.widget-container');
        const positions = new Set();
        
        existingWidgets.forEach(function(w) {
            const content = w.querySelector('.widget-content');
            if (content) {
                const row = parseInt(content.dataset.row);
                const col = parseInt(content.dataset.column);
                positions.add(row + '-' + col);
            }
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

        console.log('Position calculée:', { row, column });

        openWidgetConfigModal(draggedWidgetType, row, column);
        draggedWidgetType = null;
    });
    }

function showLimitDialog(customMessage) {
    const isCustom = !!customMessage;
    const title = isCustom ? 'Action non autorisée' : '⚠️ Limite atteinte';
    const message = customMessage || 'Vous avez atteint la limite de <strong>10 widgets</strong> autorisés pour cette version de démonstration.';
    const extraMessage = isCustom ? '' : '<p>Pour ajouter de nouveaux widgets, veuillez supprimer un widget existant ou passer à la version complète.</p>';
    
    const modal = createShadcnModal({
        title: title,
        description: `
            <p class="mb-4">${message}</p>
            ${extraMessage}
        `,
        buttons: [
            {
                label: 'Compris',
                variant: 'default',
                onClick: function(modal) {
                    document.body.removeChild(modal);
                }
            }
        ]
    });
    document.body.appendChild(modal);
}

// ========================
// SHADCN MODAL HELPER
// ========================

function createShadcnModal(options) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm';
    modal.onclick = function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    };
    
    const buttonsHTML = options.buttons ? options.buttons.map(function(btn) {
        const variantClasses = {
            'default': 'bg-primary text-primary-foreground hover:bg-primary/90',
            'destructive': 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
            'outline': 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
            'secondary': 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
            'ghost': 'hover:bg-accent hover:text-accent-foreground',
        };
        const variant = btn.variant || 'default';
        return `
            <button type="button" 
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 ${variantClasses[variant]} ${btn.class || ''}"
                    data-action="${btn.action || ''}">
                ${btn.label}
            </button>
        `;
    }).join('') : '';
    
    modal.innerHTML = `
        <div class="bg-background rounded-lg border shadow-lg p-6 max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="space-y-4">
                ${options.title ? `<h2 class="text-lg font-semibold">${options.title}</h2>` : ''}
                ${options.description ? `<div class="text-sm text-muted-foreground">${options.description}</div>` : ''}
                ${options.content || ''}
                ${buttonsHTML ? `<div class="flex gap-2 justify-end mt-6">${buttonsHTML}</div>` : ''}
            </div>
        </div>
    `;
    
    if (options.buttons) {
        options.buttons.forEach(function(btn, index) {
            const btnElement = modal.querySelectorAll('button[data-action]')[index];
            if (btnElement && btn.onClick) {
                btnElement.onclick = function() {
                    btn.onClick(modal);
                };
            }
        });
    }
    
    return modal;
}

// ========================
// MODALS DE CONFIGURATION
// ========================

function openWidgetConfigModal(type, row, column, existingConfig = null, widgetId = null) {
    const modalContent = generateConfigModalContent(type, existingConfig);
    
    const buttons = [];
    
    if (!modalContent.hideButton) {
        buttons.push({
            label: widgetId ? 'Mettre à jour' : 'Ajouter au dashboard',
            variant: 'default',
            class: 'flex-1 save-btn',
            onClick: function(modalElement) {
                if (widgetId) {
                    updateWidget(widgetId, modalElement);
                } else {
                    saveWidget(type, row, column, modalElement);
                }
            }
        });
    }
    
    buttons.push({
        label: 'Annuler',
        variant: 'outline',
        class: 'flex-1',
        onClick: function(modalElement) {
            document.body.removeChild(modalElement);
        }
    });
    
    const modal = createShadcnModal({
        title: modalContent.title,
        content: modalContent.html,
        buttons: buttons
    });
    
    document.body.appendChild(modal);
    
    if (['product_search', 'sugar_salt_comparison', 'nutriscore_comparison', 'nova_comparison', 'nutrition_pie'].indexOf(type) !== -1) {
        initProductSearch(modal, type, existingConfig);
    }
}

function generateConfigModalContent(type, existingConfig) {
    const titles = {
        'product_search': 'Recherche Produit',
        'quick_barcode_search': 'Recherche par Code-barres',
        'sugar_salt_comparison': 'Comparaison Sucre & Sel',
        'nutriscore_comparison': 'Comparaison Nutriscore',
        'nova_comparison': 'Comparaison NOVA',
        'nutrition_pie': 'Graphique Nutritionnel',
        'shopping_list': 'Ma Liste de Course'
    };

    if (type === 'shopping_list') {
        const existingShoppingList = document.querySelector('.widget-content[data-widget-type="shopping_list"]');
        
        return {
            title: titles[type],
            html: `
                <div class="rounded-lg border ${existingShoppingList ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200'} p-4">
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="${existingShoppingList ? 'text-red-600' : 'text-blue-600'} flex-shrink-0 mt-0.5">
                            ${existingShoppingList 
                                ? '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>'
                                : '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>'
                            }
                        </svg>
                        <p class="text-sm ${existingShoppingList ? 'text-red-800' : 'text-blue-800'}">
                            ${existingShoppingList 
                                ? '<strong>Vous avez déjà une liste de course.</strong><br>Vous ne pouvez en avoir qu\'une seule.' 
                                : 'Votre liste de course est prête ! Utilisez les boutons <strong class="text-green-600">+</strong> sur les autres widgets pour ajouter des produits.'
                            }
                        </p>
                    </div>
                </div>
            `,
            hideButton: !!existingShoppingList 
        };
    }

    const needsBarcode = type === 'quick_barcode_search';
    const needsSingle = ['product_search', 'nutrition_pie'].indexOf(type) !== -1;
    const needsMultiple = ['sugar_salt_comparison', 'nutriscore_comparison', 'nova_comparison'].indexOf(type) !== -1;

    let selectedBarcode = '';
    let selectedBarcodes = [];

    if (existingConfig) {
        if (existingConfig.barcode) selectedBarcode = existingConfig.barcode;
        if (existingConfig.barcodes) selectedBarcodes = existingConfig.barcodes;
    }

    let html = '<div class="space-y-4">';

    if (needsBarcode) {
        html += `
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none">Entrez le code-barres</label>
                <input type="text" 
                       class="barcode-input flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 font-mono" 
                       placeholder="Ex: 3017620422003"
                       value="${selectedBarcode}"
                       maxlength="13">
                <p class="text-xs text-muted-foreground">Code-barres à 8 ou 13 chiffres</p>
            </div>
        `;
    }

    if (needsSingle) {
        html += `
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none">Rechercher un produit</label>
                <input type="text" 
                       class="product-search-input flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                       placeholder="Tapez le nom du produit..."
                       data-single="true">
                <div class="product-search-results mt-2"></div>
                <input type="hidden" class="selected-barcode" value="${selectedBarcode}">
                ${selectedBarcode ? '<div class="mt-2 text-sm text-green-600 flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Produit sélectionné</div>' : ''}
            </div>
        `;
    }

    if (needsMultiple) {
        html += `
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none">
                    ${type === 'shopping_list' ? 'Ajouter des produits à votre liste' : 'Ajouter des produits (1 à 5)'}
                </label>
                <input type="text" 
                       class="product-search-input flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                       placeholder="Tapez le nom du produit..."
                       data-multiple="true">
                <div class="product-search-results mt-2"></div>
                <div class="selected-products mt-4 space-y-2"></div>
                <input type="hidden" class="selected-barcodes" value='${JSON.stringify(selectedBarcodes)}'>
            </div>
        `;
    }

    html += '</div>';

    return {
        title: titles[type],
        html: html
    };
}

// ========================
// RECHERCHE PRODUITS
// ========================

function initProductSearch(container, widgetType, existingConfig) {
    const input = container.querySelector('.product-search-input');
    const resultsDiv = container.querySelector('.product-search-results');
    const isSingle = input && input.dataset.single === 'true';
    const isMultiple = input && input.dataset.multiple === 'true';

    if (!input) return;

    let searchTimeout;

    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        if (currentSearchRequest) {
            currentSearchRequest = null;
        }

        const query = this.value.trim();

        if (query.length < 3) {
            resultsDiv.innerHTML = '';
            return;
        }

        resultsDiv.innerHTML = `
            <div class="flex items-center gap-2 p-3 text-sm text-muted-foreground">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                Recherche en cours...
            </div>
        `;

        searchTimeout = setTimeout(function() {
            const thisRequest = {};
            currentSearchRequest = thisRequest;

            fetch('/dashboard/search-products?q=' + encodeURIComponent(query))
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (currentSearchRequest !== thisRequest) {
                        console.log('Requête obsolète ignorée');
                        return;
                    }
                    displaySearchResults(data.products, resultsDiv, container, isSingle, isMultiple);
                })
                .catch(function(err) {
                    if (currentSearchRequest === thisRequest) {
                        console.error('Search error:', err);
                        resultsDiv.innerHTML = '<p class="text-sm text-destructive p-2">L\'API d\'Open Food Facts a rencontré une erreur. Veuillez recharger la page ou réessayer plus tard</p>';
                    }
                });
        }, 500);
    });
}

function displaySearchResults(products, resultsDiv, container, isSingle, isMultiple) {
    if (products.length === 0) {
        resultsDiv.innerHTML = '<p class="text-sm text-muted-foreground p-2">Aucun produit trouvé</p>';
        return;
    }

    resultsDiv.innerHTML = '<div class="space-y-1 max-h-60 overflow-y-auto border rounded-md">' +
        products.map(function(p) {
            return `
                <div class="product-result p-3 hover:bg-accent cursor-pointer transition border-b last:border-b-0"
                     data-barcode="${p.barcode}"
                     data-name="${p.name}"
                     data-brands="${p.brands}">
                    <div class="font-medium text-sm">${p.name}</div>
                    <div class="text-xs text-muted-foreground">${p.brands} • ${p.barcode}</div>
                    ${p.nutriscore !== 'N/A' ? '<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold mt-1 bg-green-100 text-green-800">Nutriscore ' + p.nutriscore + '</span>' : ''}
                </div>
            `;
        }).join('') +
        '</div>';

    resultsDiv.querySelectorAll('.product-result').forEach(function(result) {
        result.addEventListener('click', function() {
            const barcode = this.dataset.barcode;
            const name = this.dataset.name;
            const brands = this.dataset.brands;

            if (isSingle) {
                container.querySelector('.selected-barcode').value = barcode;
                resultsDiv.innerHTML = `
                    <div class="rounded-lg border bg-green-50 p-3 text-sm text-green-800">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            <strong>${name}</strong> sélectionné
                        </div>
                    </div>
                `;
                container.querySelector('.product-search-input').value = '';
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
        showToast('Ce produit est déjà ajouté', 'warning');
        return;
    }

    if (barcodes.length >= 5) {
        showToast('Maximum 5 produits', 'warning');
        return;
    }

    barcodes.push(barcode);
    barcodesInput.value = JSON.stringify(barcodes);

    const productCard = document.createElement('div');
    productCard.className = 'flex items-center justify-between rounded-lg border bg-accent p-3';
    productCard.innerHTML = `
        <div class="flex-1 min-w-0">
            <div class="font-medium text-sm truncate">${name}</div>
            <div class="text-xs text-muted-foreground truncate">${brands}</div>
        </div>
        <button class="remove-product inline-flex items-center justify-center rounded-md text-sm font-medium hover:bg-destructive hover:text-destructive-foreground h-8 w-8 ml-2" 
                data-barcode="${barcode}">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"/>
                <path d="m6 6 12 12"/>
            </svg>
        </button>
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

    if (type === 'shopping_list') {
        configuration = { barcodes: [] };
    } else if (type === 'quick_barcode_search') {
        const barcode = modalContainer.querySelector('.barcode-input').value.trim();
        if (!barcode || !/^\d{8,13}$/.test(barcode)) {
            showToast('Veuillez entrer un code-barres valide (8 ou 13 chiffres)', 'error');
            return;
        }
        configuration = { barcode: barcode };
    } else if (type === 'product_search' || type === 'nutrition_pie') {
        const barcode = modalContainer.querySelector('.selected-barcode').value;
        if (!barcode) {
            showToast('Veuillez sélectionner un produit', 'error');
            return;
        }
        configuration = { barcode: barcode };
    } else if (['sugar_salt_comparison', 'nutriscore_comparison', 'nova_comparison'].indexOf(type) !== -1) {
        const barcodes = JSON.parse(modalContainer.querySelector('.selected-barcodes').value || '[]');
        if (barcodes.length === 0) {
            showToast('Veuillez ajouter au moins un produit', 'error');
            return;
        }
        configuration = { barcodes: barcodes };
    }

    const saveBtn = modalContainer.querySelector('.save-btn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin mr-2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
        </svg>
        Ajout en cours...
    `;

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
            showToast('Erreur: ' + (data.error || 'Erreur inconnue'), 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Ajouter au dashboard';
        }
    })
    .catch(function(err) {
        console.error('Fetch error:', err);
        showToast('Erreur: ' + err.message, 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Ajouter au dashboard';
    });
}

// ========================
// MODIFIER WIDGET
// ========================

function updateWidget(widgetId, modalContainer) {
    const configuration = {};

    const barcodeInput = modalContainer.querySelector('.barcode-input');
    const barcodeInputSingle = modalContainer.querySelector('.selected-barcode');
    const barcodesInput = modalContainer.querySelector('.selected-barcodes');

    if (barcodeInput) {
        const barcode = barcodeInput.value.trim();
        if (!barcode || !/^\d{8,13}$/.test(barcode)) {
            showToast('Veuillez entrer un code-barres valide (8 ou 13 chiffres)', 'error');
            return;
        }
        configuration.barcode = barcode;
    } else if (barcodeInputSingle) {
        const barcode = barcodeInputSingle.value;
        if (!barcode) {
            showToast('Veuillez sélectionner un produit', 'error');
            return;
        }
        configuration.barcode = barcode;
    } else if (barcodesInput) {
        const barcodes = JSON.parse(barcodesInput.value || '[]');
        if (barcodes.length === 0) {
            showToast('Veuillez ajouter au moins un produit', 'error');
            return;
        }
        configuration.barcodes = barcodes;
    }

    const saveBtn = modalContainer.querySelector('.save-btn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin mr-2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
        </svg>
        Mise à jour...
    `;

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
            showToast('Erreur: ' + data.error, 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Mettre à jour';
        }
    })
    .catch(function(err) {
        showToast('Erreur: ' + err.message, 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Mettre à jour';
    });
}
// ========================
// AJOUTER À LA LISTE DE COURSE
// ========================

function addToShoppingList(barcode, productName) {
    fetch('/dashboard/shopping-list/add/' + barcode, {
        method: 'POST'
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showToast(data.message, 'success');
            refreshShoppingListWidget();
        } else {
            showToast(data.error, 'error');
        }
    })
    .catch(function(err) {
        console.error('Error adding to shopping list:', err);
        showToast('Erreur lors de l\'ajout', 'error');
    });
}

function removeFromShoppingList(barcode) {
    const modal = createShadcnModal({
        title: 'Confirmer la suppression',
        description: 'Voulez-vous vraiment retirer ce produit de votre liste ?',
        buttons: [
            {
                label: 'Supprimer',
                variant: 'destructive',
                onClick: function(modalElement) {
                    fetch('/dashboard/shopping-list/remove/' + barcode, {
                        method: 'DELETE'
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            showToast(data.message, 'success');
                            refreshShoppingListWidget();
                        } else {
                            showToast(data.error, 'error');
                        }
                        document.body.removeChild(modalElement);
                    })
                    .catch(function(err) {
                        console.error('Error removing from shopping list:', err);
                        showToast('Erreur lors de la suppression', 'error');
                        document.body.removeChild(modalElement);
                    });
                }
            },
            {
                label: 'Annuler',
                variant: 'outline',
                onClick: function(modalElement) {
                    document.body.removeChild(modalElement);
                }
            }
        ]
    });
    document.body.appendChild(modal);
}

function clearShoppingList() {
    const modal = createShadcnModal({
        title: 'Vider la liste',
        description: 'Voulez-vous vraiment vider toute votre liste de course ?',
        buttons: [
            {
                label: 'Vider la liste',
                variant: 'destructive',
                onClick: function(modalElement) {
                    fetch('/dashboard/shopping-list/clear', {
                        method: 'DELETE'
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            showToast(data.message, 'success');
                            refreshShoppingListWidget();
                        } else {
                            showToast(data.error, 'error');
                        }
                        document.body.removeChild(modalElement);
                    })
                    .catch(function(err) {
                        console.error('Error clearing shopping list:', err);
                        showToast('Erreur', 'error');
                        document.body.removeChild(modalElement);
                    });
                }
            },
            {
                label: 'Annuler',
                variant: 'outline',
                onClick: function(modalElement) {
                    document.body.removeChild(modalElement);
                }
            }
        ]
    });
    document.body.appendChild(modal);
}

function refreshShoppingListWidget() {
    document.querySelectorAll('.widget-content').forEach(function(widget) {
        if (widget.dataset.widgetType === 'shopping_list') {
            const widgetId = widget.dataset.widgetId;
            loadWidgetData(widgetId, 'shopping_list');
        }
    });
}

// ========================
// CHARGER WIDGETS EXISTANTS
// ========================

function loadExistingWidgets() {
    document.querySelectorAll('.widget-content').forEach(function(widget) {
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
                contentDiv.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-sm text-destructive">' + data.error + '</p></div>';
                return;
            }

            renderWidgetContent(widgetId, widgetType, data, contentDiv);
        })
        .catch(function(err) {
            console.error('Error loading widget:', err);
            contentDiv.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-sm text-destructive">Erreur de chargement</p></div>';
        });
}

function renderWidgetContent(widgetId, widgetType, data, contentDiv) {
    if (data.type === 'product_info') {
        renderProductInfo(data.product, contentDiv);
    } else if (data.type === 'quick_barcode_empty') {
        renderQuickBarcodeEmpty(contentDiv);
    } else if (data.type === 'shopping_list') {
        renderShoppingList(data.products, contentDiv);
    } else if (data.type === 'shopping_list_empty') {
        renderShoppingListEmpty(contentDiv);
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
        <div class="space-y-3 text-sm">
            <div class="flex items-start justify-between gap-2">
                <div class="font-bold text-base flex-1">${product.name}</div>
                <button class="add-to-list-btn inline-flex items-center justify-center rounded-full bg-green-600 text-white hover:bg-green-700 transition h-8 w-8 flex-shrink-0 mr-2"
                        data-barcode="${product.barcode}"
                        data-name="${product.name}"
                        title="Ajouter à ma liste de course">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/>
                        <path d="M12 5v14"/>
                    </svg>
                </button>
            </div>
            
            <div class="text-muted-foreground">
                <strong>Marque :</strong> ${product.brands}
            </div>
            
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <div class="text-muted-foreground mb-1">Code-barres</div>
                    <div class="font-mono">${product.barcode}</div>
                </div>
                <div>
                    <div class="text-muted-foreground mb-1">Quantité</div>
                    <div>${product.quantity}</div>
                </div>
            </div>

            <div class="flex gap-3 items-center">
                <div>
                    <div class="text-xs text-muted-foreground mb-1">Nutriscore</div>
                    <span class="inline-flex items-center justify-center rounded-full w-10 h-10 text-white font-bold text-lg" 
                          style="background-color: ${nutriscoreColor};">
                        ${product.nutriscore}
                    </span>
                </div>
                <div>
                    <div class="text-xs text-muted-foreground mb-1">NOVA</div>
                    <span class="inline-flex items-center justify-center rounded-full w-10 h-10 text-white font-bold text-lg" 
                          style="background-color: ${novaColor};">
                        ${product.nova}
                    </span>
                </div>
            </div>

            <div>
                <div class="text-xs text-muted-foreground mb-1">Catégories</div>
                <div class="text-xs">${product.categories}</div>
            </div>

            <div>
                <div class="text-xs text-muted-foreground mb-1">Origine</div>
                <div class="text-xs">${product.origins}</div>
            </div>

            <a href="${product.url}" 
               target="_blank" 
               class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4 py-2">
                Voir + d'informations
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-1">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                    <polyline points="15 3 21 3 21 9"/>
                    <line x1="10" x2="21" y1="14" y2="3"/>
                </svg>
            </a>
        </div>
    `;
    
    container.querySelector('.add-to-list-btn').addEventListener('click', function() {
        addToShoppingList(this.dataset.barcode, this.dataset.name);
    });
}

function renderQuickBarcodeEmpty(container) {
    container.innerHTML = `
        <div class="flex items-center justify-center h-full">
            <div class="text-center text-muted-foreground">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-2">
                    <path d="M3 5v14"/>
                    <path d="M8 5v14"/>
                    <path d="M12 5v14"/>
                    <path d="M17 5v14"/>
                    <path d="M21 5v14"/>
                </svg>
                <p class="text-sm">Aucun code-barres configuré</p>
                <p class="text-xs mt-2">Cliquez sur ⚙️ pour configurer</p>
            </div>
        </div>
    `;
}

function renderBarChart(widgetId, chartData, container) {
    container.innerHTML = '<canvas id="chart-' + widgetId + '" class="w-full h-full"></canvas>';
    
    new Chart(document.getElementById('chart-' + widgetId), {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'top',
                    labels: {
                        font: {
                            family: 'system-ui, -apple-system, sans-serif'
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'hsl(var(--border))'
                    }
                },
                x: {
                    grid: {
                        color: 'hsl(var(--border))'
                    }
                }
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

    container.innerHTML = '<div class="space-y-2">' +
        products.map(function(p) {
            const color = nutriscoreColors[p.nutriscore] || nutriscoreColors['N/A'];
            return `
                <div class="flex items-center justify-between p-2 rounded-lg border bg-card hover:bg-accent transition">
                    <div class="flex-1 min-w-0 mr-2">
                        <div class="font-medium text-sm truncate">${p.name}</div>
                        <div class="text-xs text-muted-foreground"><strong>Marque :</strong> ${p.brands}</div>
                        <div class="text-xs text-muted-foreground font-mono">${p.barcode}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center rounded-full w-10 h-10 text-white font-bold text-lg flex-shrink-0"
                              style="background-color: ${color}">
                            ${p.nutriscore}
                        </span>
                        <button class="add-to-list-btn inline-flex items-center justify-center rounded-full bg-green-600 text-white hover:bg-green-700 transition h-8 w-8 flex-shrink-0 mr-2"
                                data-barcode="${p.barcode}"
                                data-name="${p.name}"
                                title="Ajouter à ma liste de course">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14"/>
                                <path d="M12 5v14"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        }).join('') +
        '</div>';
    
    container.querySelectorAll('.add-to-list-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            addToShoppingList(this.dataset.barcode, this.dataset.name);
        });
    });
}

function renderNovaComparison(products, container) {
    const novaColors = {
        '1': '#4CAF50',
        '2': '#FFC107',
        '3': '#FF9800',
        '4': '#F44336',
        'N/A': '#CCCCCC'
    };

    container.innerHTML = '<div class="space-y-2">' +
        products.map(function(p) {
            const color = novaColors[p.nova] || novaColors['N/A'];
            return `
                <div class="flex items-center justify-between p-2 rounded-lg border bg-card hover:bg-accent transition">
                    <div class="flex-1 min-w-0 mr-2">
                        <div class="font-medium text-sm truncate">${p.name}</div>
                        <div class="text-xs text-muted-foreground"><strong>Marque :</strong> ${p.brands}</div>
                        <div class="text-xs text-muted-foreground font-mono">${p.barcode}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center rounded-full w-10 h-10 text-white font-bold text-lg flex-shrink-0"
                              style="background-color: ${color}">
                            ${p.nova}
                        </span>
                        <button class="add-to-list-btn inline-flex items-center justify-center rounded-full bg-green-600 text-white hover:bg-green-700 transition h-8 w-8 flex-shrink-0 mr-2"
                                data-barcode="${p.barcode}"
                                data-name="${p.name}"
                                title="Ajouter à ma liste de course">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14"/>
                                <path d="M12 5v14"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        }).join('') +
        '</div>';
    
    container.querySelectorAll('.add-to-list-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            addToShoppingList(this.dataset.barcode, this.dataset.name);
        });
    });
}

function renderPieChart(widgetId, data, container) {
    container.innerHTML = `
        <div class="text-center mb-2 font-semibold text-sm">${data.product_name}</div>
        <canvas id="chart-${widgetId}" class="w-full" height="220"></canvas>
    `;
    
    new Chart(document.getElementById('chart-' + widgetId), {
        type: 'pie',
        data: data.data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: {
                        font: {
                            family: 'system-ui, -apple-system, sans-serif',
                            size: 11
                        },
                        padding: 8
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += context.parsed + 'g pour 100g';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

function renderShoppingList(products, container) {
    if (products.length === 0) {
        renderShoppingListEmpty(container);
        return;
    }

    container.innerHTML = `
        <div class="space-y-3">
            <div class="flex items-center justify-between pb-2 border-b">
                <h4 class="font-semibold text-sm">Ma liste (${products.length}/20)</h4>
                <div class="flex gap-2">
                    <a href="/dashboard/shopping-list/download" 
                       class="inline-flex items-center justify-center rounded-md text-xs bg-green-600 text-white hover:bg-green-700 h-8 px-3"
                       title="Télécharger en PDF">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" x2="12" y1="15" y2="3"/>
                        </svg>
                        PDF
                    </a>
                    <button class="clear-list-btn text-xs text-destructive hover:underline">
                        Vider
                    </button>
                </div>
            </div>
            <div class="space-y-2 max-h-[220px] overflow-y-auto">
                ${products.map(function(p) {
                    return `
                        <div class="flex items-center gap-2 p-2 rounded-lg border bg-card hover:bg-accent transition">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-sm truncate">${p.name}</div>
                                <div class="text-xs text-muted-foreground">
                                    <strong>Marque :</strong> ${p.brands}
                                </div>
                                <div class="text-xs text-muted-foreground">${p.quantity}</div>
                            </div>
                            <a href="${p.url}" 
                               target="_blank" 
                               class="inline-flex items-center justify-center hover:bg-primary/10 rounded-md h-8 w-8 flex-shrink-0"
                               title="Voir sur OpenFoodFacts">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                    <polyline points="15 3 21 3 21 9"/>
                                    <line x1="10" x2="21" y1="14" y2="3"/>
                                </svg>
                            </a>
                            <button class="remove-from-list-btn inline-flex items-center justify-center hover:bg-destructive hover:text-destructive-foreground rounded-md h-8 w-8 flex-shrink-0"
                                    data-barcode="${p.barcode}"
                                    title="Retirer de la liste">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6 6 18"/>
                                    <path d="m6 6 12 12"/>
                                </svg>
                            </button>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
    
    container.querySelectorAll('.remove-from-list-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            removeFromShoppingList(this.dataset.barcode);
        });
    });
    
    container.querySelector('.clear-list-btn').addEventListener('click', function() {
        clearShoppingList();
    });
}

function renderShoppingListEmpty(container) {
    container.innerHTML = `
        <div class="flex items-center justify-center h-full">
            <div class="text-center text-muted-foreground">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-2">
                    <circle cx="8" cy="21" r="1"/>
                    <circle cx="19" cy="21" r="1"/>
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                </svg>
                <p class="text-sm">Votre liste est vide</p>
                <p class="text-xs mt-2">Utilisez les boutons + sur les widgets</p>
            </div>
        </div>
    `;
}

// ========================
// SUPPRIMER WIDGETS
// ========================

function initRemoveButtons() {
    document.querySelectorAll('.remove-widget').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const widgetId = this.dataset.widgetId;
            
            const modal = createShadcnModal({
                title: 'Supprimer le widget',
                description: 'Voulez-vous vraiment supprimer ce widget ?',
                buttons: [
                    {
                        label: 'Supprimer',
                        variant: 'destructive',
                        onClick: function(modalElement) {
                            fetch('/dashboard/widget/' + widgetId + '/remove', {
                                method: 'DELETE'
                            })
                            .then(function(res) { return res.json(); })
                            .then(function(data) {
                                if (data.success) {
                                    location.reload();
                                } else {
                                    showToast('Erreur: ' + data.error, 'error');
                                }
                            });
                        }
                    },
                    {
                        label: 'Annuler',
                        variant: 'outline',
                        onClick: function(modalElement) {
                            document.body.removeChild(modalElement);
                        }
                    }
                ]
            });
            
            document.body.appendChild(modal);
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