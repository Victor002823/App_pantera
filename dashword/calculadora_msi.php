<?php
/**
 * Componente: Calculadora MSI Openpay (botón flotante + modal)
 * Uso: incluir este archivo una sola vez por página, justo antes de </body>
 *   <?php include 'ruta/calculadora_msi.php'; ?>
 *
 * Todo va prefijado con "msicalc-" para no chocar con estilos/JS existentes.
 */
?>
<style>
    .msicalc-fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background-color: #004696;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: none;
        z-index: 9999;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .msicalc-fab:hover {
        transform: scale(1.06);
        box-shadow: 0 6px 16px rgba(0,0,0,0.3);
    }

    .msicalc-fab:active {
        transform: scale(0.95);
    }

    .msicalc-fab svg {
        width: 26px;
        height: 26px;
        stroke: #ffffff;
    }

    .msicalc-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 9998;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color-scheme: light only;
    }

    .msicalc-overlay.open {
        display: flex;
    }

    .msicalc-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        width: 100%;
        max-width: 480px;
        padding: 25px;
        border: 1px solid #e0e0e0;
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
        color: #333333;
        box-sizing: border-box;
        color-scheme: light only;
        forced-color-adjust: none;
    }

    .msicalc-close {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: none;
        background: #f4f6f9;
        color: #666666;
        font-size: 18px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .msicalc-close:hover {
        background: #e0e0e0;
    }

    .msicalc-reset {
        position: absolute;
        top: 14px;
        right: 54px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: none;
        background: #f4f6f9;
        color: #666666;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s ease, transform 0.15s ease;
    }

    .msicalc-reset:hover {
        background: #e0e0e0;
    }

    .msicalc-reset:active {
        transform: rotate(-45deg);
    }

    .msicalc-reset svg {
        width: 16px;
        height: 16px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .msicalc-card h2 {
        margin-top: 0;
        color: #004696;
        text-align: center;
        font-size: 22px;
    }

    .msicalc-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 4px 0 20px;
    }

    .msicalc-header-icon {
        width: 46px;
        height: 46px;
        flex-shrink: 0;
        border-radius: 14px;
        background-color: #004696;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0,70,150,0.25);
    }

    .msicalc-header-icon svg {
        width: 24px;
        height: 24px;
        stroke: #ffffff !important;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .msicalc-header-text h2 {
        margin: 0;
        text-align: left;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: -0.01em;
        color: #0f172a !important;
    }

    .msicalc-header-text p {
        margin: 2px 0 0;
        font-size: 12px;
        color: #94a3b8 !important;
        font-weight: 500;
    }

    .msicalc-tabs {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 2px solid #e0e0e0;
    }

    .msicalc-tab {
        flex: 1;
        text-align: center;
        padding: 10px;
        cursor: pointer;
        font-weight: 600;
        color: #666666;
        transition: all 0.2s ease;
    }

    .msicalc-tab.active {
        color: #004696;
        border-bottom: 3px solid #004696;
        margin-bottom: -2px;
    }

    .msicalc-input-group {
        margin-bottom: 15px;
    }

    .msicalc-card label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333333;
    }

    .msicalc-input-wrapper {
        position: relative;
    }

    .msicalc-input-wrapper span {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #666666;
        font-weight: 600;
    }

    .msicalc-card input[type="number"],
    .msicalc-card select {
        width: 100%;
        padding: 12px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 16px;
        box-sizing: border-box;
        outline: none;
        background-color: #ffffff;
        transition: border-color 0.2s;
    }

    .msicalc-card input[type="number"] {
        padding-left: 30px;
    }

    .msicalc-card input[type="number"]:focus,
    .msicalc-card select:focus {
        border-color: #004696;
    }

    .msicalc-results {
        background-color: #fafafa;
        border-radius: 8px;
        padding: 15px;
        border: 1px solid #e0e0e0;
        margin-top: 20px;
    }

    .msicalc-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 14px;
        color: #666666;
    }

    .msicalc-row.msicalc-highlight {
        background-color: #fff3cd;
        padding: 8px;
        border-radius: 4px;
        border-left: 4px solid #fd7e14;
        color: #856404;
    }

    .msicalc-row.msicalc-total {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px dashed #e0e0e0;
        font-size: 18px;
        font-weight: bold;
        color: #004696;
    }

    .msicalc-row.msicalc-net {
        color: #28a745;
    }

    .msicalc-badge {
        font-size: 11px;
        background-color: #e6f0fa;
        color: #004696;
        padding: 4px 8px;
        border-radius: 4px;
        text-align: center;
        margin-bottom: 15px;
        font-weight: 500;
    }
</style>

<!-- Botón flotante -->
<button class="msicalc-fab" onclick="msicalcToggle(true)" aria-label="Abrir calculadora">
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="4" y="2" width="16" height="20" rx="2"></rect>
        <line x1="8" y1="6" x2="16" y2="6"></line>
        <line x1="8" y1="10" x2="8" y2="10"></line>
        <line x1="12" y1="10" x2="12" y2="10"></line>
        <line x1="16" y1="10" x2="16" y2="10"></line>
        <line x1="8" y1="14" x2="8" y2="14"></line>
        <line x1="12" y1="14" x2="12" y2="14"></line>
        <line x1="16" y1="14" x2="16" y2="14"></line>
        <line x1="8" y1="18" x2="8" y2="18"></line>
        <line x1="12" y1="18" x2="12" y2="18"></line>
        <line x1="16" y1="18" x2="16" y2="18"></line>
    </svg>
</button>

<!-- Overlay con la calculadora -->
<div class="msicalc-overlay" id="msicalcOverlay">
    <div class="msicalc-card">
        <button class="msicalc-close" onclick="msicalcToggle(false)" aria-label="Cerrar">✕</button>
        <button class="msicalc-reset" onclick="msicalcResetForm()" aria-label="Nueva consulta" title="Nueva consulta">
            <svg viewBox="0 0 24 24">
                <path d="M3 12a9 9 0 1 1 3 6.7"></path>
                <polyline points="3 16 3 21 8 21"></polyline>
            </svg>
        </button>

        <div class="msicalc-header">
            <div class="msicalc-header-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                    <line x1="8" y1="6" x2="16" y2="6"></line>
                    <line x1="8" y1="10" x2="8" y2="10"></line>
                    <line x1="12" y1="10" x2="12" y2="10"></line>
                    <line x1="16" y1="10" x2="16" y2="10"></line>
                    <line x1="8" y1="14" x2="8" y2="14"></line>
                    <line x1="12" y1="14" x2="12" y2="14"></line>
                    <line x1="16" y1="14" x2="16" y2="14"></line>
                </svg>
            </div>
            <div class="msicalc-header-text">
                <h2>Calculador de Cobros con MSI</h2>
                <p>Openpay · 2.9% + $2.50 MXN + IVA</p>
            </div>
        </div>
        <div class="msicalc-badge">Más sobretasa según el plazo seleccionado</div>

        <div class="msicalc-tabs">
            <div class="msicalc-tab active" id="msicalc-tab-direct" onclick="msicalcSwitch('direct')">¿Cuánto me llegará?</div>
            <div class="msicalc-tab" id="msicalc-tab-inverse" onclick="msicalcSwitch('inverse')">¿Cuánto debo cobrar?</div>
        </div>

        <div class="msicalc-input-group">
            <label id="msicalc-input-label" for="msicalc-amount">Monto a cobrar al cliente:</label>
            <div class="msicalc-input-wrapper">
                <span>$</span>
                <input type="number" id="msicalc-amount" placeholder="0.00" min="0" step="0.01" oninput="msicalcCalculate()">
            </div>
        </div>

        <div class="msicalc-input-group">
            <label for="msicalc-plots">Plazo de Mensualidades:</label>
            <select id="msicalc-plots" onchange="msicalcCalculate()">
                <option value="1">Pago Único (Sin MSI)</option>
                <option value="3">3 Meses Sin Intereses</option>
                <option value="6">6 Meses Sin Intereses</option>
                <option value="9">9 Meses Sin Intereses</option>
                <option value="12">12 Meses Sin Intereses</option>
            </select>
        </div>

        <div class="msicalc-results">
            <div class="msicalc-row">
                <span>Monto Base Comercial (Bruto):</span>
                <span id="msicalc-res-base">$0.00</span>
            </div>
            <div id="msicalc-monthly-container" class="msicalc-row msicalc-highlight" style="display: none;">
                <span>Mensualidad del cliente:</span>
                <span id="msicalc-res-monthly" style="font-weight: bold;">$0.00</span>
            </div>
            <div class="msicalc-row">
                <span>Comisión Fija Openpay ($2.50):</span>
                <span id="msicalc-res-fixed">$0.00</span>
            </div>
            <div class="msicalc-row">
                <span>Comisión Variable + MSI:</span>
                <span id="msicalc-res-variable">$0.00</span>
            </div>
            <div class="msicalc-row">
                <span>IVA de la Comisión (16%):</span>
                <span id="msicalc-res-iva">$0.00</span>
            </div>
            <div class="msicalc-row" style="font-weight: 500;">
                <span>Retención Total de Pasarela:</span>
                <span id="msicalc-res-total-fee" style="color: #dc3545;">$0.00</span>
            </div>
            <div class="msicalc-row msicalc-total" id="msicalc-total-row">
                <span id="msicalc-total-label">Recibes en tu banco (Neto):</span>
                <span id="msicalc-res-final">$0.00</span>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let msicalcMode = 'direct';

    const msicalcBaseRate = 0.029; // 2.9%
    const msicalcSurcharges = {
        1: 0.0,
        3: 0.045,
        6: 0.075,
        9: 0.100,
        12: 0.125
    };

    window.msicalcToggle = function(open) {
        document.getElementById('msicalcOverlay').classList.toggle('open', open);
        if (open) msicalcResetForm();
    };

    window.msicalcResetForm = function() {
        document.getElementById('msicalc-amount').value = '';
        document.getElementById('msicalc-plots').value = '1';
        msicalcMode = 'direct';
        document.getElementById('msicalc-tab-direct').classList.add('active');
        document.getElementById('msicalc-tab-inverse').classList.remove('active');
        document.getElementById('msicalc-input-label').innerText = "Monto a cobrar al cliente:";
        document.getElementById('msicalc-total-label').innerText = "Recibes en tu banco (Neto):";
        document.getElementById('msicalc-total-row').className = "msicalc-row msicalc-total msicalc-net";
        msicalcReset();
        document.getElementById('msicalc-amount').focus();
    };

    document.getElementById('msicalcOverlay').addEventListener('click', function(e) {
        if (e.target === this) msicalcToggle(false);
    });

    window.msicalcSwitch = function(mode) {
        msicalcMode = mode;
        document.getElementById('msicalc-tab-direct').classList.toggle('active', mode === 'direct');
        document.getElementById('msicalc-tab-inverse').classList.toggle('active', mode === 'inverse');

        const label = document.getElementById('msicalc-input-label');
        const totalLabel = document.getElementById('msicalc-total-label');
        const totalRow = document.getElementById('msicalc-total-row');

        if (mode === 'direct') {
            label.innerText = "Monto a cobrar al cliente:";
            totalLabel.innerText = "Recibes en tu banco (Neto):";
            totalRow.className = "msicalc-row msicalc-total msicalc-net";
        } else {
            label.innerText = "Monto neto que deseas recibir:";
            totalLabel.innerText = "Monto total a cobrar al cliente:";
            totalRow.className = "msicalc-row msicalc-total";
        }
        msicalcCalculate();
    };

    function msicalcFormatMoney(value) {
        return '$' + value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    window.msicalcCalculate = function() {
        const inputVal = parseFloat(document.getElementById('msicalc-amount').value) || 0;
        const plots = parseInt(document.getElementById('msicalc-plots').value) || 1;

        let grossAmount = 0;
        let netAmount = 0;
        let fixedFee = 2.50;

        let totalVariableRate = msicalcBaseRate + msicalcSurcharges[plots];

        if (inputVal <= 0) {
            msicalcReset();
            return;
        }

        if (msicalcMode === 'direct') {
            grossAmount = inputVal;
            let variableFee = grossAmount * totalVariableRate;
            let ivaFee = (fixedFee + variableFee) * 0.16;
            let totalFee = fixedFee + variableFee + ivaFee;
            netAmount = grossAmount - totalFee;
        } else {
            grossAmount = (inputVal + (fixedFee * 1.16)) / (1 - (totalVariableRate * 1.16));
            netAmount = inputVal;
        }

        let finalVariableFee = grossAmount * totalVariableRate;
        let finalIvaFee = (fixedFee + finalVariableFee) * 0.16;
        let finalTotalFee = fixedFee + finalVariableFee + finalIvaFee;

        document.getElementById('msicalc-res-base').innerText = msicalcFormatMoney(grossAmount);
        document.getElementById('msicalc-res-fixed').innerText = msicalcFormatMoney(fixedFee);
        document.getElementById('msicalc-res-variable').innerText = msicalcFormatMoney(finalVariableFee) + " (" + (totalVariableRate * 100).toFixed(1) + "%)";
        document.getElementById('msicalc-res-iva').innerText = msicalcFormatMoney(finalIvaFee);
        document.getElementById('msicalc-res-total-fee').innerText = msicalcFormatMoney(finalTotalFee);
        document.getElementById('msicalc-res-final').innerText = msicalcFormatMoney(msicalcMode === 'direct' ? netAmount : grossAmount);

        const monthlyContainer = document.getElementById('msicalc-monthly-container');
        if (plots > 1) {
            monthlyContainer.style.display = 'flex';
            let monthlyPayment = grossAmount / plots;
            document.getElementById('msicalc-res-monthly').innerText = msicalcFormatMoney(monthlyPayment) + " x " + plots + " meses";
        } else {
            monthlyContainer.style.display = 'none';
        }
    };

    function msicalcReset() {
        document.getElementById('msicalc-res-base').innerText = "$0.00";
        document.getElementById('msicalc-res-fixed').innerText = "$0.00";
        document.getElementById('msicalc-res-variable').innerText = "$0.00";
        document.getElementById('msicalc-res-iva').innerText = "$0.00";
        document.getElementById('msicalc-res-total-fee').innerText = "$0.00";
        document.getElementById('msicalc-res-final').innerText = "$0.00";
        document.getElementById('msicalc-monthly-container').style.display = 'none';
    }

    msicalcSwitch('direct');
})();
</script>
