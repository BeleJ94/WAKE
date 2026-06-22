(function () {
    'use strict';

    var shell = document.querySelector('[data-app-shell]');
    var sidebarOverlay = document.querySelector('[data-sidebar-overlay]');

    function closeSidebar() {
        if (shell) {
            shell.classList.remove('sidebar-open');
        }
    }

    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (shell) {
                shell.classList.toggle('sidebar-open');
            }
        });
    });

    document.querySelectorAll('[data-sidebar-close]').forEach(function (button) {
        button.addEventListener('click', closeSidebar);
    });

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    document.querySelectorAll('[data-nav-group-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var group = button.closest('[data-nav-group]');
            var willOpen = group && !group.classList.contains('is-open');

            document.querySelectorAll('[data-nav-group]').forEach(function (otherGroup) {
                if (otherGroup === group || otherGroup.classList.contains('has-active-item')) {
                    return;
                }
                otherGroup.classList.remove('is-open');
                var otherButton = otherGroup.querySelector('[data-nav-group-toggle]');
                if (otherButton) {
                    otherButton.setAttribute('aria-expanded', 'false');
                }
            });

            if (group) {
                group.classList.toggle('is-open', willOpen);
                button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            }
        });
    });

    document.querySelectorAll('.nav-subitem, .nav-item').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 980) {
                closeSidebar();
            }
        });
    });

    document.querySelectorAll('[data-modal-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            var modal = document.getElementById(button.getAttribute('data-modal-open'));

            if (modal) {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            var modal = button.closest('.modal');

            if (modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }
        });
    });

    var treasuryEditModal = document.getElementById('treasury-edit-modal');
    var treasuryEditForm = document.querySelector('[data-treasury-edit-form]');

    function formatTreasuryAmount(value, currency) {
        return Number(value || 0).toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' ' + currency;
    }

    document.querySelectorAll('[data-treasury-edit]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!treasuryEditModal || !treasuryEditForm) {
                return;
            }

            var currency = button.getAttribute('data-account-currency') || 'USD';
            treasuryEditForm.querySelector('[data-edit-account-id]').value = button.getAttribute('data-account-id') || '';
            treasuryEditForm.querySelector('[data-edit-account-name]').value = button.getAttribute('data-account-name') || '';
            treasuryEditForm.querySelector('[data-edit-account-type]').value = button.getAttribute('data-account-type') || 'Caisse';
            treasuryEditForm.querySelector('[data-edit-account-currency]').value = currency;
            treasuryEditForm.querySelector('[data-edit-account-responsible]').value = button.getAttribute('data-account-responsible') || '';
            treasuryEditForm.querySelector('[data-edit-account-status]').value = button.getAttribute('data-account-status') || 'active';
            treasuryEditForm.querySelector('[data-edit-account-notes]').value = button.getAttribute('data-account-notes') || '';
            treasuryEditModal.querySelector('[data-edit-account-balance]').textContent = formatTreasuryAmount(button.getAttribute('data-account-balance'), currency);
            treasuryEditModal.querySelector('[data-edit-account-opening]').textContent = formatTreasuryAmount(button.getAttribute('data-account-opening'), currency);
            treasuryEditModal.querySelector('#treasury-edit-title').textContent = 'Modifier · ' + (button.getAttribute('data-account-name') || 'Compte');
            var accountType = button.getAttribute('data-account-type') || 'Caisse';
            var accountIcon = accountType === 'Banque' ? 'bank' : (accountType === 'Mobile Money' ? 'phone' : 'cash-stack');
            var iconTarget = treasuryEditModal.querySelector('[data-edit-account-icon]');
            if (iconTarget) {
                iconTarget.innerHTML = '<i class="bi bi-' + accountIcon + '"></i>';
            }

            treasuryEditModal.classList.add('is-open');
            treasuryEditModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            treasuryEditForm.querySelector('[data-edit-account-name]').focus();
        });
    });

    if (treasuryEditModal) {
        treasuryEditModal.querySelectorAll('[data-modal-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                document.body.classList.remove('modal-open');
            });
        });
    }

    var treasuryAccountDetailModal = document.getElementById('treasury-account-detail-modal');
    var treasuryAccountDetailTrigger = null;

    function escapeTreasuryDetailHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatTreasuryDetailDate(value, withTime) {
        if (!value) {
            return '—';
        }
        var date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleDateString('fr-FR', withTime ? {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        } : {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    }

    function closeTreasuryAccountDetail() {
        if (!treasuryAccountDetailModal) {
            return;
        }
        treasuryAccountDetailModal.classList.remove('is-open');
        treasuryAccountDetailModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        if (treasuryAccountDetailTrigger) {
            treasuryAccountDetailTrigger.focus();
        }
    }

    function renderTreasuryAccountDetail(payload) {
        var account = payload.account;
        var summary = payload.summary;
        var movements = payload.movements || [];
        var currency = account.currency || 'USD';
        var icon = account.type === 'Banque' ? 'bank' : (account.type === 'Mobile Money' ? 'phone' : 'cash-stack');
        var variationClass = account.variation >= 0 ? 'is-positive' : 'is-negative';
        var variationPrefix = account.variation > 0 ? '+' : '';

        treasuryAccountDetailModal.querySelector('[data-account-detail-icon]').innerHTML = '<i class="bi bi-' + icon + '"></i>';
        treasuryAccountDetailModal.querySelector('[data-account-detail-name]').textContent = account.name;
        treasuryAccountDetailModal.querySelector('[data-account-detail-type]').textContent = account.type;
        treasuryAccountDetailModal.querySelector('[data-account-detail-currency]').textContent = currency;
        var status = treasuryAccountDetailModal.querySelector('[data-account-detail-status]');
        status.textContent = account.status_label;
        status.className = account.status === 'active' ? 'is-active' : 'is-inactive';
        treasuryAccountDetailModal.querySelector('[data-account-detail-balance]').textContent = formatTreasuryAmount(account.current_balance, currency);
        var variation = treasuryAccountDetailModal.querySelector('[data-account-detail-variation]');
        variation.className = variationClass;
        variation.textContent = variationPrefix + formatTreasuryAmount(account.variation, currency) + ' depuis l’ouverture';
        treasuryAccountDetailModal.querySelector('[data-account-detail-opening]').textContent = formatTreasuryAmount(account.opening_balance, currency);
        treasuryAccountDetailModal.querySelector('[data-account-detail-inflow]').textContent = formatTreasuryAmount(summary.total_inflow, currency);
        treasuryAccountDetailModal.querySelector('[data-account-detail-outflow]').textContent = formatTreasuryAmount(summary.total_outflow, currency);
        treasuryAccountDetailModal.querySelector('[data-account-detail-count]').textContent = String(summary.movement_count || 0);
        treasuryAccountDetailModal.querySelector('[data-account-detail-last]').textContent = summary.last_movement_at ? 'Dernier : ' + formatTreasuryDetailDate(summary.last_movement_at, false) : 'Aucune opération';
        treasuryAccountDetailModal.querySelector('[data-account-detail-responsible]').textContent = account.responsible;
        treasuryAccountDetailModal.querySelector('[data-account-detail-created]').textContent = formatTreasuryDetailDate(account.created_at, false);
        treasuryAccountDetailModal.querySelector('[data-account-detail-notes]').textContent = account.notes;
        treasuryAccountDetailModal.querySelector('[data-account-detail-history-count]').textContent = movements.length + ' opération(s)';
        treasuryAccountDetailModal.querySelector('[data-account-detail-excel]').href = payload.exports.excel;
        treasuryAccountDetailModal.querySelector('[data-account-detail-pdf]').href = payload.exports.pdf;

        treasuryAccountDetailModal.querySelector('[data-account-detail-movements]').innerHTML = movements.length ? movements.map(function (movement) {
            var movementClass = movement.type === 'inflow' ? 'is-inflow' : 'is-outflow';
            var amountPrefix = movement.type === 'inflow' ? '+' : '−';
            return '<tr>' +
                '<td><strong>' + escapeTreasuryDetailHtml(movement.reference) + '</strong><small>' + escapeTreasuryDetailHtml(movement.description) + '</small></td>' +
                '<td>' + escapeTreasuryDetailHtml(formatTreasuryDetailDate(movement.created_at, true)) + '</td>' +
                '<td><span class="account-movement-type ' + movementClass + '">' + escapeTreasuryDetailHtml(movement.type_label) + '</span></td>' +
                '<td class="text-right"><strong class="' + movementClass + '">' + amountPrefix + formatTreasuryAmount(movement.amount, currency) + '</strong></td>' +
                '<td class="text-right">' + formatTreasuryAmount(movement.balance_after, currency) + '</td>' +
                '<td>' + escapeTreasuryDetailHtml(movement.created_by) + '</td>' +
                '</tr>';
        }).join('') : '<tr><td colspan="6"><div class="account-detail-empty"><i class="bi bi-receipt"></i><strong>Aucun mouvement</strong><span>Ce compte ne possède pas encore d’historique financier.</span></div></td></tr>';

        treasuryAccountDetailModal.querySelector('[data-account-detail-loading]').hidden = true;
        treasuryAccountDetailModal.querySelector('[data-account-detail-content]').hidden = false;
    }

    document.querySelectorAll('[data-treasury-account-detail]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!treasuryAccountDetailModal) {
                return;
            }
            treasuryAccountDetailTrigger = button;
            treasuryAccountDetailModal.querySelector('[data-account-detail-loading]').hidden = false;
            treasuryAccountDetailModal.querySelector('[data-account-detail-loading]').classList.remove('is-error');
            treasuryAccountDetailModal.querySelector('[data-account-detail-loading]').innerHTML = '<i class="bi bi-arrow-repeat"></i><strong>Préparation de la fiche du compte…</strong>';
            treasuryAccountDetailModal.querySelector('[data-account-detail-content]').hidden = true;
            treasuryAccountDetailModal.classList.add('is-open');
            treasuryAccountDetailModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');

            var detailUrl = treasuryAccountDetailModal.getAttribute('data-account-detail-url');
            fetch(detailUrl + '?id=' + encodeURIComponent(button.getAttribute('data-account-id')), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Compte indisponible');
                    }
                    return response.json();
                })
                .then(renderTreasuryAccountDetail)
                .catch(function () {
                    var loading = treasuryAccountDetailModal.querySelector('[data-account-detail-loading]');
                    loading.classList.add('is-error');
                    loading.innerHTML = '<i class="bi bi-exclamation-triangle"></i><strong>Impossible de charger les détails du compte.</strong>';
                });
        });
    });

    if (treasuryAccountDetailModal) {
        treasuryAccountDetailModal.querySelectorAll('[data-account-detail-close]').forEach(function (button) {
            button.addEventListener('click', closeTreasuryAccountDetail);
        });
        treasuryAccountDetailModal.addEventListener('click', function (event) {
            if (event.target === treasuryAccountDetailModal) {
                closeTreasuryAccountDetail();
            }
        });
    }

    var dashboardDetailModal = document.querySelector('[data-dashboard-detail-modal]');
    var dashboardDetailTrigger = null;

    function escapeDashboardHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function closeDashboardDetail() {
        if (!dashboardDetailModal) {
            return;
        }
        dashboardDetailModal.classList.remove('is-open');
        dashboardDetailModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        if (dashboardDetailTrigger) {
            dashboardDetailTrigger.focus();
        }
    }

    function renderDashboardDetail(payload) {
        var state = dashboardDetailModal.querySelector('[data-dashboard-detail-state]');
        var content = dashboardDetailModal.querySelector('[data-dashboard-detail-content]');
        var title = dashboardDetailModal.querySelector('[data-dashboard-detail-title]');
        var description = dashboardDetailModal.querySelector('[data-dashboard-detail-description]');
        var count = dashboardDetailModal.querySelector('[data-dashboard-detail-count]');
        var head = dashboardDetailModal.querySelector('[data-dashboard-detail-head]');
        var body = dashboardDetailModal.querySelector('[data-dashboard-detail-body]');
        var excel = dashboardDetailModal.querySelector('[data-dashboard-export-excel]');
        var pdf = dashboardDetailModal.querySelector('[data-dashboard-export-pdf]');

        title.textContent = payload.title || 'Détail';
        description.textContent = payload.description || '';
        count.textContent = (payload.rows || []).length + ' ligne(s)';
        head.innerHTML = '<tr>' + (payload.columns || []).map(function (column) {
            return '<th>' + escapeDashboardHtml(column) + '</th>';
        }).join('') + '</tr>';
        body.innerHTML = (payload.rows || []).length ? payload.rows.map(function (row) {
            return '<tr>' + row.map(function (value) {
                return '<td>' + escapeDashboardHtml(value) + '</td>';
            }).join('') + '</tr>';
        }).join('') : '<tr><td colspan="' + Math.max(1, (payload.columns || []).length) + '"><div class="empty-state"><i class="bi bi-inbox"></i> Aucune donnée disponible.</div></td></tr>';
        excel.href = payload.exports.excel;
        pdf.href = payload.exports.pdf;
        state.hidden = true;
        content.hidden = false;
    }

    function openDashboardDetail(trigger) {
        if (!dashboardDetailModal) {
            return;
        }
        var type = trigger.getAttribute('data-dashboard-detail');
        var state = dashboardDetailModal.querySelector('[data-dashboard-detail-state]');
        var content = dashboardDetailModal.querySelector('[data-dashboard-detail-content]');
        dashboardDetailTrigger = trigger;
        state.hidden = false;
        state.classList.remove('is-error');
        state.innerHTML = '<i class="bi bi-arrow-repeat" aria-hidden="true"></i><span>Chargement des données...</span>';
        content.hidden = true;
        dashboardDetailModal.classList.add('is-open');
        dashboardDetailModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');

        var detailUrl = trigger.getAttribute('data-detail-url') || ((window.BASE_URL || '') + '/dashboard/details');
        fetch(detailUrl + (detailUrl.indexOf('?') === -1 ? '?' : '&') + 'type=' + encodeURIComponent(type), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Détail indisponible');
                }
                return response.json();
            })
            .then(renderDashboardDetail)
            .catch(function () {
                state.classList.add('is-error');
                state.innerHTML = '<i class="bi bi-exclamation-triangle"></i><span>Impossible de charger ce détail.</span>';
            });
    }

    document.querySelectorAll('[data-dashboard-detail]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            openDashboardDetail(trigger);
        });
        if (trigger.tagName !== 'BUTTON' && trigger.tagName !== 'A') {
            trigger.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openDashboardDetail(trigger);
                }
            });
        }
    });

    document.querySelectorAll('[data-dashboard-detail-close]').forEach(function (button) {
        button.addEventListener('click', closeDashboardDetail);
    });

    if (dashboardDetailModal) {
        dashboardDetailModal.addEventListener('click', function (event) {
            if (event.target === dashboardDetailModal) {
                closeDashboardDetail();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
            closeDashboardDetail();
            closeTreasuryAccountDetail();

            document.querySelectorAll('.modal.is-open').forEach(function (modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            });
        }
    });

    document.querySelectorAll('[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                form.classList.add('was-validated');
                var firstInvalid = form.querySelector(':invalid');

                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
        });
    });

    function setupCanvas(canvas) {
        var ratio = window.devicePixelRatio || 1;
        var rect = canvas.getBoundingClientRect();
        canvas.width = Math.max(1, Math.floor(rect.width * ratio));
        canvas.height = Math.max(1, Math.floor(rect.height * ratio));
        var context = canvas.getContext('2d');
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        return { context: context, width: rect.width, height: rect.height };
    }

    function currencyLabel(value) {
        return Math.round(value / 1000) + 'k';
    }

    function drawRevenueExpense(canvas, payload) {
        var chart = setupCanvas(canvas);
        var ctx = chart.context;
        var width = chart.width;
        var height = chart.height;
        var padding = { top: 24, right: 18, bottom: 38, left: 46 };
        var values = payload.revenues.concat(payload.expenses);
        var maxValue = Math.max.apply(Math, values) * 1.18;
        var plotWidth = width - padding.left - padding.right;
        var plotHeight = height - padding.top - padding.bottom;
        var step = plotWidth / payload.labels.length;
        var barWidth = Math.min(26, step * 0.26);

        ctx.clearRect(0, 0, width, height);
        ctx.font = '12px Inter, Arial, sans-serif';
        ctx.fillStyle = '#6b7280';
        ctx.strokeStyle = '#e6ebf2';
        ctx.lineWidth = 1;

        for (var i = 0; i <= 4; i += 1) {
            var y = padding.top + (plotHeight / 4) * i;
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            ctx.stroke();
            ctx.fillText(currencyLabel(maxValue - (maxValue / 4) * i), 8, y + 4);
        }

        payload.labels.forEach(function (label, index) {
            var x = padding.left + step * index + step / 2;
            var revenueHeight = (payload.revenues[index] / maxValue) * plotHeight;
            var expenseHeight = (payload.expenses[index] / maxValue) * plotHeight;

            ctx.fillStyle = '#0f9f6e';
            ctx.fillRect(x - barWidth - 3, padding.top + plotHeight - revenueHeight, barWidth, revenueHeight);
            ctx.fillStyle = '#f59e0b';
            ctx.fillRect(x + 3, padding.top + plotHeight - expenseHeight, barWidth, expenseHeight);
            ctx.fillStyle = '#6b7280';
            ctx.fillText(label, x - 12, height - 12);
        });

        ctx.fillStyle = '#0f9f6e';
        ctx.fillRect(width - 152, 10, 10, 10);
        ctx.fillStyle = '#374151';
        ctx.fillText('Revenus', width - 136, 19);
        ctx.fillStyle = '#f59e0b';
        ctx.fillRect(width - 78, 10, 10, 10);
        ctx.fillStyle = '#374151';
        ctx.fillText('Dépenses', width - 62, 19);
    }

    function drawServiceExpenses(canvas, payload) {
        var chart = setupCanvas(canvas);
        var ctx = chart.context;
        var width = chart.width;
        var height = chart.height;
        var centerX = width / 2;
        var centerY = height / 2;
        var radius = Math.min(width, height) / 2 - 18;
        var total = payload.reduce(function (sum, item) { return sum + Number(item.value); }, 0);
        var start = -Math.PI / 2;

        ctx.clearRect(0, 0, width, height);

        payload.forEach(function (item) {
            var slice = (Number(item.value) / total) * Math.PI * 2;
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, start, start + slice);
            ctx.closePath();
            ctx.fillStyle = item.color;
            ctx.fill();
            start += slice;
        });

        ctx.beginPath();
        ctx.arc(centerX, centerY, radius * 0.58, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.fillStyle = '#111827';
        ctx.font = '800 22px Inter, Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(currencyLabel(total) + ' USD', centerX, centerY + 4);
        ctx.font = '12px Inter, Arial, sans-serif';
        ctx.fillStyle = '#6b7280';
        ctx.fillText('Total mois', centerX, centerY + 24);
        ctx.textAlign = 'left';
    }

    function drawReportBars(canvas, payload) {
        var chart = setupCanvas(canvas);
        var ctx = chart.context;
        var width = chart.width;
        var height = chart.height;
        var labels = payload.labels || [];
        var values = payload.values || [];
        var maxValue = Math.max.apply(Math, values.concat([1]));
        var padding = { top: 18, right: 20, bottom: 44, left: 54 };
        var plotWidth = width - padding.left - padding.right;
        var plotHeight = height - padding.top - padding.bottom;
        var barGap = 12;
        var barWidth = Math.max(18, (plotWidth - (labels.length - 1) * barGap) / Math.max(labels.length, 1));

        ctx.clearRect(0, 0, width, height);
        ctx.font = '12px Inter, Arial, sans-serif';
        ctx.strokeStyle = '#e6ebf2';
        ctx.fillStyle = '#6b7280';
        ctx.lineWidth = 1;

        for (var i = 0; i <= 4; i += 1) {
            var y = padding.top + (plotHeight / 4) * i;
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            ctx.stroke();
        }

        labels.forEach(function (label, index) {
            var value = Number(values[index] || 0);
            var x = padding.left + index * (barWidth + barGap);
            var barHeight = (value / maxValue) * plotHeight;
            var y = padding.top + plotHeight - barHeight;
            ctx.fillStyle = index % 2 === 0 ? '#0f9f6e' : '#1d4ed8';
            ctx.fillRect(x, y, barWidth, barHeight);
            ctx.fillStyle = '#374151';
            ctx.save();
            ctx.translate(x + barWidth / 2, height - 12);
            ctx.rotate(-Math.PI / 6);
            ctx.textAlign = 'right';
            ctx.fillText(String(label).slice(0, 16), 0, 0);
            ctx.restore();
        });
    }

    function renderCharts() {
        document.querySelectorAll('[data-chart]').forEach(function (canvas) {
            var payload = JSON.parse(canvas.getAttribute('data-payload') || '{}');
            var type = canvas.getAttribute('data-chart');

            if (type === 'revenue-expense') {
                drawRevenueExpense(canvas, payload);
            }

            if (type === 'service-expenses') {
                drawServiceExpenses(canvas, payload);
            }

            if (type === 'report-bars') {
                drawReportBars(canvas, payload);
            }
        });
    }

    renderCharts();
    window.addEventListener('resize', renderCharts);

    var requestAmountInput = document.querySelector('[data-request-amount]');
    var requestCurrencyInput = document.querySelector('[data-request-currency]');
    var requestSummaryAmount = document.querySelector('[data-request-summary-amount]');

    function refreshRequestSummary() {
        if (!requestSummaryAmount) {
            return;
        }
        var amount = Number(requestAmountInput ? requestAmountInput.value : 0);
        var currency = requestCurrencyInput ? requestCurrencyInput.value : 'USD';
        requestSummaryAmount.textContent = amount.toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' ' + currency;
    }

    if (requestAmountInput) {
        requestAmountInput.addEventListener('input', refreshRequestSummary);
    }
    if (requestCurrencyInput) {
        requestCurrencyInput.addEventListener('change', refreshRequestSummary);
    }
    refreshRequestSummary();

    var supportingUpload = document.querySelector('[data-supporting-upload]');
    if (supportingUpload) {
        var supportingInput = supportingUpload.querySelector('[data-supporting-input]');
        var supportingDropzone = supportingUpload.querySelector('[data-supporting-dropzone]');
        var supportingPreview = supportingUpload.querySelector('[data-supporting-preview]');
        var supportingPreviewFrame = supportingUpload.querySelector('[data-supporting-preview-frame]');
        var supportingFileName = supportingUpload.querySelector('[data-supporting-file-name]');
        var supportingFileSize = supportingUpload.querySelector('[data-supporting-file-size]');
        var supportingFileIcon = supportingUpload.querySelector('[data-supporting-file-icon]');
        var supportingRemove = supportingUpload.querySelector('[data-supporting-remove]');
        var supportingObjectUrl = null;
        var supportingPrompt = supportingDropzone.querySelector('strong').textContent;

        function clearSupportingPreview() {
            if (supportingObjectUrl) {
                URL.revokeObjectURL(supportingObjectUrl);
                supportingObjectUrl = null;
            }
            supportingInput.value = '';
            supportingPreview.hidden = true;
            supportingDropzone.hidden = false;
            supportingPreviewFrame.innerHTML = '';
            supportingUpload.classList.remove('has-file', 'has-error');
            supportingDropzone.querySelector('strong').textContent = supportingPrompt;
        }

        function previewSupportingFile(file) {
            if (!file) {
                clearSupportingPreview();
                return;
            }

            var allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (allowedTypes.indexOf(file.type) === -1 || file.size > 5 * 1024 * 1024) {
                clearSupportingPreview();
                supportingUpload.classList.add('has-error');
                supportingDropzone.querySelector('strong').textContent = file.size > 5 * 1024 * 1024
                    ? 'Le fichier dépasse 5 Mo'
                    : 'Format de fichier non autorisé';
                return;
            }

            supportingObjectUrl = URL.createObjectURL(file);
            supportingFileName.textContent = file.name;
            supportingFileSize.textContent = (file.size / 1048576).toLocaleString('fr-FR', {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1
            }) + ' Mo';

            if (file.type.indexOf('image/') === 0) {
                supportingPreviewFrame.innerHTML = '<img src="' + supportingObjectUrl + '" alt="Aperçu de la pièce justificative">';
                supportingFileIcon.innerHTML = '<i class="bi bi-file-earmark-image"></i>';
            } else {
                supportingPreviewFrame.innerHTML = '<iframe src="' + supportingObjectUrl + '#toolbar=0" title="Aperçu PDF"></iframe>';
                supportingFileIcon.innerHTML = '<i class="bi bi-file-earmark-pdf"></i>';
            }

            supportingDropzone.hidden = true;
            supportingPreview.hidden = false;
            supportingUpload.classList.add('has-file');
            supportingUpload.classList.remove('has-error');
        }

        supportingInput.addEventListener('change', function () {
            previewSupportingFile(supportingInput.files[0]);
        });

        supportingRemove.addEventListener('click', clearSupportingPreview);

        ['dragenter', 'dragover'].forEach(function (eventName) {
            supportingDropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                supportingDropzone.classList.add('is-dragging');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            supportingDropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                supportingDropzone.classList.remove('is-dragging');
            });
        });

        supportingDropzone.addEventListener('drop', function (event) {
            var file = event.dataTransfer.files[0];
            if (!file) {
                return;
            }
            var transfer = new DataTransfer();
            transfer.items.add(file);
            supportingInput.files = transfer.files;
            previewSupportingFile(file);
        });
    }

    function filterTable(input) {
        var table = input.getAttribute('data-target') ? document.querySelector(input.getAttribute('data-target')) : input.closest('.panel').querySelector('table');
        if (!table) {
            return;
        }
        var query = (input.value || '').toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().indexOf(query) !== -1 ? '' : 'none';
        });
    }

    document.querySelectorAll('[data-table-search]').forEach(function (input) {
        input.addEventListener('input', function () {
            filterTable(input);
        });
    });

    document.querySelectorAll('[data-table-filter]').forEach(function (select) {
        select.addEventListener('change', function () {
            var table = select.closest('.panel').querySelector('table');
            var column = Number(select.getAttribute('data-table-filter'));
            if (!table) {
                return;
            }
            table.querySelectorAll('tbody tr').forEach(function (row) {
                var cell = row.children[column];
                row.style.display = !select.value || (cell && cell.textContent.indexOf(select.value) !== -1) ? '' : 'none';
            });
        });
    });

    document.querySelectorAll('[data-table-status]').forEach(function (select) {
        select.addEventListener('change', function () {
            var table = document.querySelector(select.getAttribute('data-target'));
            if (!table) {
                return;
            }
            table.querySelectorAll('tbody tr').forEach(function (row) {
                row.style.display = !select.value || row.getAttribute('data-status') === select.value ? '' : 'none';
            });
        });
    });

    document.querySelectorAll('[data-treasury-datatable]').forEach(function (dataTable) {
        var table = dataTable.querySelector('.treasury-table');
        if (!table) {
            return;
        }

        var body = table.querySelector('tbody');
        var rows = Array.prototype.slice.call(body.querySelectorAll('tr'));
        var search = dataTable.querySelector('[data-treasury-search]');
        var filters = Array.prototype.slice.call(dataTable.querySelectorAll('[data-treasury-filter]'));
        var pageSize = dataTable.querySelector('[data-treasury-page-size]');
        var pagination = dataTable.querySelector('[data-treasury-pagination]');
        var resultCount = dataTable.querySelector('[data-treasury-result-count]');
        var range = dataTable.querySelector('[data-treasury-range]');
        var empty = dataTable.querySelector('[data-treasury-empty]');
        var reset = dataTable.querySelector('[data-treasury-reset]');
        var currentPage = 1;
        var sortKey = '';
        var sortDirection = 1;

        function normalizeTreasuryText(value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function filteredTreasuryRows() {
            var query = normalizeTreasuryText(search ? search.value : '');

            return rows.filter(function (row) {
                if (query && normalizeTreasuryText(row.textContent).indexOf(query) === -1) {
                    return false;
                }

                return filters.every(function (filter) {
                    var value = filter.value;
                    var key = filter.getAttribute('data-treasury-filter');
                    return !value || row.getAttribute('data-' + key) === value;
                });
            }).sort(function (first, second) {
                if (!sortKey) {
                    return 0;
                }
                var firstValue = first.getAttribute('data-' + sortKey.replace(/[A-Z]/g, function (letter) {
                    return '-' + letter.toLowerCase();
                })) || '';
                var secondValue = second.getAttribute('data-' + sortKey.replace(/[A-Z]/g, function (letter) {
                    return '-' + letter.toLowerCase();
                })) || '';

                if (sortKey === 'balance') {
                    return (Number(firstValue) - Number(secondValue)) * sortDirection;
                }

                return firstValue.localeCompare(secondValue, 'fr', { sensitivity: 'base' }) * sortDirection;
            });
        }

        function renderTreasuryPagination(totalPages) {
            pagination.innerHTML = '';
            if (totalPages <= 1) {
                return;
            }

            function addButton(label, page, disabled, active, icon) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'treasury-page-button' + (active ? ' is-active' : '');
                button.disabled = disabled;
                button.setAttribute('aria-label', label);
                button.innerHTML = icon ? '<i class="bi bi-' + icon + '"></i>' : String(label);
                button.addEventListener('click', function () {
                    currentPage = page;
                    renderTreasuryTable();
                });
                pagination.appendChild(button);
            }

            addButton('Page précédente', Math.max(1, currentPage - 1), currentPage === 1, false, 'chevron-left');
            var start = Math.max(1, Math.min(currentPage - 2, totalPages - 4));
            var end = Math.min(totalPages, start + 4);
            for (var page = start; page <= end; page += 1) {
                addButton(page, page, false, page === currentPage);
            }
            addButton('Page suivante', Math.min(totalPages, currentPage + 1), currentPage === totalPages, false, 'chevron-right');
        }

        function renderTreasuryTable() {
            var matches = filteredTreasuryRows();
            var size = Number(pageSize ? pageSize.value : 10) || 10;
            var totalPages = Math.max(1, Math.ceil(matches.length / size));
            currentPage = Math.min(currentPage, totalPages);
            var start = (currentPage - 1) * size;
            var visibleRows = matches.slice(start, start + size);

            rows.forEach(function (row) {
                row.hidden = true;
            });
            matches.forEach(function (row) {
                body.appendChild(row);
            });
            visibleRows.forEach(function (row) {
                row.hidden = false;
            });

            resultCount.textContent = String(matches.length);
            empty.hidden = matches.length !== 0;
            table.hidden = matches.length === 0;
            range.textContent = matches.length
                ? 'Affichage de ' + (start + 1) + ' à ' + Math.min(start + size, matches.length) + ' sur ' + matches.length + ' compte(s)'
                : 'Aucun compte ne correspond aux filtres';
            renderTreasuryPagination(totalPages);
        }

        if (search) {
            search.addEventListener('input', function () {
                currentPage = 1;
                renderTreasuryTable();
            });
        }
        filters.forEach(function (filter) {
            filter.addEventListener('change', function () {
                currentPage = 1;
                renderTreasuryTable();
            });
        });
        if (pageSize) {
            pageSize.addEventListener('change', function () {
                currentPage = 1;
                renderTreasuryTable();
            });
        }
        dataTable.querySelectorAll('[data-treasury-sort]').forEach(function (button) {
            button.addEventListener('click', function () {
                var nextKey = button.getAttribute('data-treasury-sort');
                sortDirection = sortKey === nextKey ? sortDirection * -1 : 1;
                sortKey = nextKey;
                dataTable.querySelectorAll('[data-treasury-sort]').forEach(function (otherButton) {
                    otherButton.classList.remove('is-sorted');
                    otherButton.removeAttribute('data-sort-direction');
                });
                button.classList.add('is-sorted');
                button.setAttribute('data-sort-direction', sortDirection === 1 ? 'asc' : 'desc');
                renderTreasuryTable();
            });
        });
        if (reset) {
            reset.addEventListener('click', function () {
                if (search) {
                    search.value = '';
                }
                filters.forEach(function (filter) {
                    filter.value = '';
                });
                currentPage = 1;
                sortKey = '';
                sortDirection = 1;
                dataTable.querySelectorAll('[data-treasury-sort]').forEach(function (button) {
                    button.classList.remove('is-sorted');
                    button.removeAttribute('data-sort-direction');
                });
                renderTreasuryTable();
                if (search) {
                    search.focus();
                }
            });
        }

        renderTreasuryTable();
    });

    document.querySelectorAll('[data-transfer-form]').forEach(function (form) {
        var source = form.querySelector('[data-transfer-source]');
        var destination = form.querySelector('[data-transfer-destination]');
        var amount = form.querySelector('[data-transfer-amount]');
        var rate = form.querySelector('[data-transfer-rate]');

        function selectedTransferOption(select) {
            return select && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
        }

        function transferCurrency(option) {
            return option ? option.getAttribute('data-currency') || '' : '';
        }

        function transferName(option, fallback) {
            if (!option || !option.value) {
                return fallback;
            }
            return option.textContent.split(' · ')[0];
        }

        function refreshTransferPreview() {
            var sourceOption = selectedTransferOption(source);
            var destinationOption = selectedTransferOption(destination);
            var sourceCurrency = transferCurrency(sourceOption);
            var destinationCurrency = transferCurrency(destinationOption);
            var sourceAmount = Number(amount.value || 0);
            var exchangeRate = Number(rate.value || 0);
            var sameCurrency = sourceCurrency && destinationCurrency && sourceCurrency === destinationCurrency;

            if (sameCurrency) {
                rate.value = '1';
                rate.readOnly = true;
                exchangeRate = 1;
            } else {
                rate.readOnly = false;
            }

            var destinationAmount = sourceAmount * exchangeRate;
            form.querySelector('[data-transfer-source-currency]').textContent = sourceCurrency || '—';
            form.querySelector('[data-transfer-source-balance]').textContent = sourceOption && sourceOption.value
                ? 'Disponible : ' + formatTreasuryAmount(sourceOption.getAttribute('data-balance'), sourceCurrency)
                : 'Sélectionnez un compte source.';
            form.querySelector('[data-transfer-destination-balance]').textContent = destinationOption && destinationOption.value
                ? 'Solde actuel : ' + formatTreasuryAmount(destinationOption.getAttribute('data-balance'), destinationCurrency)
                : 'Sélectionnez un compte destinataire.';
            form.querySelector('[data-transfer-received]').textContent = formatTreasuryAmount(destinationAmount, destinationCurrency || '—');
            form.querySelector('[data-transfer-rate-hint]').textContent = sourceCurrency && destinationCurrency
                ? (sameCurrency ? 'Aucune conversion nécessaire.' : '1 ' + sourceCurrency + ' = ' + exchangeRate.toLocaleString('fr-FR', { maximumFractionDigits: 6 }) + ' ' + destinationCurrency)
                : 'Sélectionnez les deux comptes.';
            form.querySelector('[data-transfer-summary-source]').textContent = transferName(sourceOption, 'Compte source');
            form.querySelector('[data-transfer-summary-destination]').textContent = transferName(destinationOption, 'Compte destinataire');
            form.querySelector('[data-transfer-summary-debit]').textContent = formatTreasuryAmount(sourceAmount, sourceCurrency || '—');
            form.querySelector('[data-transfer-summary-credit]').textContent = formatTreasuryAmount(destinationAmount, destinationCurrency || '—');

            Array.prototype.forEach.call(destination.options, function (option) {
                option.disabled = !!source.value && option.value === source.value;
            });
            if (destination.value === source.value && source.value) {
                destination.value = '';
            }
        }

        [source, destination, amount, rate].forEach(function (field) {
            if (!field) {
                return;
            }
            field.addEventListener(field.tagName === 'SELECT' ? 'change' : 'input', refreshTransferPreview);
        });
        refreshTransferPreview();
    });

    document.querySelectorAll('[data-balance-account]').forEach(function (select) {
        select.addEventListener('change', function () {
            var form = select.closest('form');
            var result = form ? form.querySelector('[data-balance-result]') : document.querySelector('[data-balance-result]');
            if (!result) {
                return;
            }
            if (!select.value) {
                result.classList.remove('is-ok', 'is-danger');
                result.textContent = 'Sélectionnez un compte pour contrôler son solde.';
                return;
            }
            var amount = select.getAttribute('data-amount') || '0';
            fetch(window.location.origin + window.location.pathname.replace(/fund_requests\/approve.*/, '') + 'ajax/treasury_accounts/balance?id=' + encodeURIComponent(select.value) + '&amount=' + encodeURIComponent(amount))
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    result.classList.remove('is-ok', 'is-danger');
                    if (payload.sufficient) {
                        result.classList.add('is-ok');
                        result.textContent = 'Solde suffisant : ' + Number(payload.balance).toLocaleString('fr-FR') + ' ' + payload.currency;
                    } else {
                        result.classList.add('is-danger');
                        result.textContent = 'Solde insuffisant : ' + Number(payload.balance).toLocaleString('fr-FR') + ' ' + payload.currency;
                    }
                });
        });
    });

    document.querySelectorAll('[data-approval-form]').forEach(function (form) {
        var choices = form.querySelectorAll('[data-decision-choice]');
        var submit = form.querySelector('[data-approval-submit]');

        function refreshApprovalDecision() {
            var selected = form.querySelector('[data-decision-choice]:checked');
            var decision = selected ? selected.value : '';

            form.querySelectorAll('[data-decision-panel]').forEach(function (panel) {
                var active = panel.getAttribute('data-decision-panel') === decision;
                panel.hidden = !active;
                panel.querySelectorAll('select, textarea, input').forEach(function (field) {
                    field.disabled = !active;
                });
            });

            form.querySelectorAll('.decision-choice').forEach(function (choice) {
                choice.classList.toggle('is-selected', !!choice.querySelector('input:checked'));
            });

            if (submit) {
                submit.disabled = decision === '';
                submit.classList.toggle('is-reject', decision === 'reject');
                submit.innerHTML = decision === 'reject'
                    ? '<i class="bi bi-x-circle"></i> Confirmer le rejet'
                    : '<i class="bi bi-shield-check"></i> Confirmer l’approbation';
            }

            var validation = form.querySelector('[data-ajax-validation-result]');
            if (validation && decision === '') {
                validation.remove();
            }
        }

        choices.forEach(function (choice) {
            choice.addEventListener('change', refreshApprovalDecision);
        });

        refreshApprovalDecision();
    });

    document.querySelectorAll('[data-load-request]').forEach(function (button) {
        button.addEventListener('click', function () {
            var modal = document.getElementById('request-detail-modal');
            var content = document.querySelector('[data-request-detail-content]');
            if (!modal || !content) {
                return;
            }
            content.textContent = 'Chargement...';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            fetch(window.location.origin + window.location.pathname.replace(/fund_requests.*/, '') + 'ajax/fund_requests/details?id=' + encodeURIComponent(button.getAttribute('data-load-request')))
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload.found) {
                        content.textContent = 'Demande introuvable.';
                        return;
                    }
                    content.innerHTML = '<div class="detail-grid"><article><span>Référence</span><strong>' + payload.request.reference + '</strong></article><article><span>Statut</span><strong>' + payload.request.status + '</strong></article><article><span>Montant demandé</span><strong>' + Number(payload.request.total_amount).toLocaleString('fr-FR') + ' ' + payload.request.currency + '</strong></article><article><span>Service</span><strong>' + payload.request.department + '</strong></article></div>';
                });
        });
    });

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    document.querySelectorAll('[data-status-badge]').forEach(function (badge) {
        var body = new URLSearchParams();
        body.append('_csrf', csrfToken);
        body.append('id', badge.getAttribute('data-status-badge'));

        fetch(window.location.origin + window.location.pathname.replace(/fund_requests.*/, '') + 'ajax/fund_requests/status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload.found) {
                    return;
                }
                badge.textContent = payload.status;
                badge.className = 'badge ' + payload.badge;
            })
            .catch(function () {});
    });

    var invoiceLines = document.querySelector('[data-invoice-lines]');
    function bindInvoiceLineActions() {
        if (!invoiceLines) {
            return;
        }
        invoiceLines.querySelectorAll('[data-remove-line]').forEach(function (button) {
            button.onclick = function () {
                var rows = invoiceLines.querySelectorAll('.invoice-line:not(.invoice-line-head)');
                if (rows.length > 1) {
                    button.closest('.invoice-line').remove();
                }
            };
        });
    }

    bindInvoiceLineActions();
    document.querySelectorAll('[data-add-invoice-line]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!invoiceLines) {
                return;
            }
            invoiceLines.insertAdjacentHTML('beforeend', '<div class="invoice-line"><input name="description[]" placeholder="Nouvelle ligne"><input type="number" step="0.01" min="0" name="quantity[]" value="1"><input type="number" step="0.01" min="0" name="unit_price[]"><input type="number" step="0.01" min="0" name="unit_cost[]"><input type="number" step="0.01" min="0" name="tax_rate[]" value="0"><button class="btn-icon" type="button" data-remove-line title="Retirer"><i class="bi bi-x-lg"></i></button></div>');
            bindInvoiceLineActions();
        });
    });

    function validationTypeFor(form) {
        var action = form.getAttribute('action') || '';
        if (action.indexOf('fund_requests/store') !== -1) { return 'fund_request'; }
        if (action.indexOf('fund_requests/approve') !== -1) { return 'approval'; }
        if (action.indexOf('fund_requests/payment') !== -1) { return 'fund_payment'; }
        if (action.indexOf('construction/projects/store') !== -1 || action.indexOf('construction/projects/update') !== -1) { return 'project'; }
        if (action.indexOf('construction/daily_reports/store') !== -1) { return 'daily_report'; }
        if (action.indexOf('invoices/store') !== -1) { return 'invoice'; }
        if (action.indexOf('invoices/payment') !== -1) { return 'invoice_payment'; }
        if (action.indexOf('deliveries/store') !== -1) { return 'delivery'; }
        return form.getAttribute('data-ajax-validate') || '';
    }

    function ensureValidationBox(form) {
        var box = form.querySelector('[data-ajax-validation-result]');
        if (!box) {
            box = document.createElement('div');
            box.className = 'ajax-validation-result';
            box.setAttribute('data-ajax-validation-result', '');
            form.insertBefore(box, form.firstChild);
        }
        return box;
    }

    function renderValidation(form, payload) {
        var box = ensureValidationBox(form);
        var errors = payload.errors || {};
        var warnings = payload.warnings || [];
        var parts = [];
        Object.keys(errors).forEach(function (key) {
            parts.push('<li>' + errors[key] + '</li>');
        });
        warnings.forEach(function (warning) {
            parts.push('<li>' + warning + '</li>');
        });
        if (parts.length === 0) {
            box.className = 'ajax-validation-result is-ok';
            box.innerHTML = 'Validation dynamique OK.';
            return;
        }
        box.className = 'ajax-validation-result ' + (Object.keys(errors).length > 0 ? 'is-error' : 'is-warning');
        box.innerHTML = '<ul>' + parts.join('') + '</ul>';
    }

    function debounce(callback, delay) {
        var timer = null;
        return function () {
            window.clearTimeout(timer);
            var args = arguments;
            timer = window.setTimeout(function () {
                callback.apply(null, args);
            }, delay);
        };
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        var type = validationTypeFor(form);
        if (!type) {
            return;
        }
        var run = debounce(function () {
            if (type === 'approval' && !form.querySelector('[name="decision"]:checked')) {
                var existing = form.querySelector('[data-ajax-validation-result]');
                if (existing) {
                    existing.remove();
                }
                return;
            }
            var body = new FormData(form);
            body.append('type', type);
            fetch((window.BASE_URL || '') + '/ajax/validate', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) { renderValidation(form, payload); })
                .catch(function () {});
        }, 450);
        form.addEventListener('input', run);
        form.addEventListener('change', run);
    });

    document.querySelectorAll('[data-ajax-action]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var body = new FormData(form);
            body.append('ajax', '1');
            fetch(form.getAttribute('action'), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (payload.ok) {
                        var item = form.closest('.notification-item');
                        if (item) {
                            item.classList.remove('is-unread');
                            form.outerHTML = '<span class="badge badge-success">Lu</span>';
                        } else {
                            document.querySelectorAll('.notification-item.is-unread').forEach(function (row) {
                                row.classList.remove('is-unread');
                            });
                        }
                        var counter = document.querySelector('[data-notification-count]');
                        if (counter) {
                            counter.textContent = payload.unread > 99 ? '99+' : String(payload.unread || 0);
                            if (!payload.unread) {
                                counter.remove();
                            }
                        }
                    }
                })
                .catch(function () { form.submit(); });
        });
    });

    document.querySelectorAll('[data-dropdown-toggle]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            var id = button.getAttribute('data-dropdown-toggle');
            document.querySelectorAll('[data-dropdown-menu]').forEach(function (menu) {
                if (menu.id !== id) {
                    menu.classList.remove('is-open');
                }
            });
            var menu = document.getElementById(id);
            if (menu) {
                menu.classList.toggle('is-open');
            }
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('[data-dropdown-menu]').forEach(function (menu) {
            menu.classList.remove('is-open');
        });
    });

    document.querySelectorAll('[data-dropdown-menu]').forEach(function (menu) {
        menu.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    document.querySelectorAll('.table-responsive').forEach(function (wrap) {
        var table = wrap.querySelector('.data-table');
        if (!table || table.classList.contains('dashboard-detail-table') || table.classList.contains('account-detail-table') || wrap.closest('[data-treasury-datatable]') || wrap.previousElementSibling && wrap.previousElementSibling.classList && wrap.previousElementSibling.classList.contains('table-density-toggle')) {
            return;
        }
        var toggle = document.createElement('div');
        toggle.className = 'table-density-toggle';
        toggle.innerHTML = '<button class="btn btn-secondary" type="button">Mode compact</button>';
        wrap.parentNode.insertBefore(toggle, wrap);
        toggle.querySelector('button').addEventListener('click', function () {
            table.classList.toggle('is-compact');
            toggle.querySelector('button').textContent = table.classList.contains('is-compact') ? 'Mode confortable' : 'Mode compact';
        });
    });

    function normalizedText(node) {
        return (node.textContent || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    var wakeUiLabels = {
        'Draft': 'Brouillon',
        'Pending': 'En attente',
        'Approved': 'Approuvé',
        'Rejected': 'Rejeté',
        'Paid': 'Payé',
        'Cancelled': 'Annulé',
        'Executed': 'Exécuté',
        'Sent': 'Envoyé',
        'Validated': 'Validé',
        'Converted': 'Converti',
        'Open': 'Ouvert',
        'Partially Paid': 'Partiellement payé',
        'Overdue': 'En retard',
        'Prepared': 'Préparé',
        'Partial': 'Partiel',
        'Partially Delivered': 'Partiellement livré',
        'Delivered': 'Livré',
        'Invoiced': 'Facturé',
        'Issued': 'Émis',
        'Planning': 'Planification',
        'In Progress': 'En cours',
        'On Hold': 'En pause',
        'Completed': 'Terminé',
        'Active': 'Actif',
        'Suspended': 'Suspendu',
        'Expired': 'Expiré',
        'Closed': 'Clôturé',
        'active': 'Actif',
        'inactive': 'Inactif',
        'inflow': 'Entrée',
        'outflow': 'Sortie',
        'Cash': 'Espèces',
        'Bank': 'Banque',
        'Wallet': 'Portefeuille'
    };

    document.querySelectorAll('.badge').forEach(function (badge) {
        var text = badge.textContent.trim();
        if (wakeUiLabels[text]) {
            badge.textContent = wakeUiLabels[text];
        }
    });

    document.querySelectorAll('select option').forEach(function (option) {
        var text = option.textContent.trim();
        if (wakeUiLabels[text]) {
            option.textContent = wakeUiLabels[text];
        }
    });

    function iconForText(text, fallback) {
        var rules = [
            ['demande de fonds|fund|soumettre|direction', 'clipboard-check'],
            ['caisse|banque|tresorerie|compte', 'bank'],
            ['paiement|payer|payee|paid', 'credit-card'],
            ['facture|facturation|receipt|imprimer|pdf', 'receipt-cutoff'],
            ['client|portefeuille', 'briefcase'],
            ['produit|catalogue|stock', 'boxes'],
            ['devis|quotation', 'file-earmark-text'],
            ['commande|order', 'cart-check'],
            ['livraison|truck', 'truck'],
            ['projet|construction|chantier|cockpit|travaux', 'building-gear'],
            ['rapport|report|graph|dashboard', 'bar-chart-line'],
            ['agent|personnel|placement|role|utilisateur|admin', 'people'],
            ['notification|alerte', 'bell'],
            ['audit|journal|trace', 'journal-text'],
            ['retour', 'arrow-left'],
            ['nouveau|nouvelle|creer|ajouter', 'plus-circle'],
            ['enregistrer|confirmer|valider|approuver', 'check2-circle'],
            ['rejeter|annuler|supprimer|retirer', 'x-circle'],
            ['modifier|edit', 'pencil-square'],
            ['voir|ouvrir|detail', 'eye'],
            ['filtrer', 'funnel'],
            ['reinitialiser', 'arrow-clockwise'],
            ['marge|revenu|solde|budget|depense|cout|montant|total', 'cash-coin'],
            ['avancement|progression|performance', 'activity']
        ];

        for (var index = 0; index < rules.length; index += 1) {
            if (new RegExp(rules[index][0]).test(text)) {
                return rules[index][1];
            }
        }

        return fallback || 'chevron-right';
    }

    function prependIcon(node, iconName, className) {
        if (!node || node.querySelector('i.bi')) {
            return;
        }
        var icon = document.createElement('i');
        icon.className = 'bi bi-' + iconName + (className ? ' ' + className : '');
        icon.setAttribute('aria-hidden', 'true');
        node.insertBefore(icon, node.firstChild);
    }

    document.querySelectorAll('.btn, .button, .link-button').forEach(function (button) {
        prependIcon(button, iconForText(normalizedText(button), 'arrow-right-circle'));
    });

    document.querySelectorAll('.kpi-card').forEach(function (card) {
        if (card.querySelector('.kpi-icon')) {
            return;
        }
        var label = card.querySelector('.kpi-meta span, > span, h3, strong');
        var holder = card.querySelector('.kpi-meta') || card;
        var iconWrap = document.createElement('span');
        iconWrap.className = 'kpi-icon';
        iconWrap.setAttribute('aria-hidden', 'true');
        iconWrap.innerHTML = '<i class="bi bi-' + iconForText(normalizedText(label || card), 'activity') + '"></i>';
        holder.insertBefore(iconWrap, holder.firstChild);
    });

    document.querySelectorAll('.panel-header h2, .panel-header h3, .section-header h2').forEach(function (heading) {
        if (heading.parentNode && heading.parentNode.querySelector('.panel-title-icon')) {
            return;
        }
        var icon = document.createElement('span');
        icon.className = 'panel-title-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = '<i class="bi bi-' + iconForText(normalizedText(heading.parentNode || heading), 'layout-text-window') + '"></i>';
        heading.parentNode.insertBefore(icon, heading);
    });

    document.querySelectorAll('.empty-state').forEach(function (emptyState) {
        if (!emptyState.querySelector('i.bi')) {
            prependIcon(emptyState, iconForText(normalizedText(emptyState), 'inbox'), 'empty-state-icon');
        }
    });

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.ajaxAction !== undefined) {
                return;
            }
            if (event.defaultPrevented || !form.checkValidity()) {
                return;
            }
            var button = form.querySelector('button[type="submit"], .btn-primary');
            if (button) {
                button.classList.add('is-loading');
            }
        });
    });

    var globalSearch = document.querySelector('[data-global-search]');
    if (globalSearch) {
        var searchInput = globalSearch.querySelector('input[type="search"]');
        var results = globalSearch.querySelector('[data-global-search-results]');
        var searchTargets = [];
        document.querySelectorAll('.sidebar-nav a, .data-table tbody tr, .kpi-card, .panel').forEach(function (node) {
            var link = node.matches('a') ? node.getAttribute('href') : (node.querySelector('a[href]') || {}).href || '';
            var label = node.textContent.replace(/\s+/g, ' ').trim();
            if (label.length > 2) {
                searchTargets.push({ label: label, href: link });
            }
        });
        searchInput.addEventListener('input', debounce(function () {
            var query = searchInput.value.trim().toLowerCase();
            if (query.length < 2) {
                results.classList.remove('is-open');
                results.innerHTML = '';
                return;
            }
            var matches = searchTargets.filter(function (item) {
                return item.label.toLowerCase().indexOf(query) !== -1;
            }).slice(0, 8);
            results.innerHTML = matches.length ? matches.map(function (item) {
                var label = item.label.slice(0, 86);
                return item.href ? '<a href="' + item.href + '">' + label + '</a>' : '<span>' + label + '</span>';
            }).join('') : '<span>Aucun résultat local</span>';
            results.classList.add('is-open');
        }, 160));
    }
}());
