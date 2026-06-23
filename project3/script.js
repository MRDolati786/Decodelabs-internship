// ──────────────────────────────────────────────────────────────
//  CONFIGURATION – POINT TO YOUR PHP API
// ──────────────────────────────────────────────────────────────

// If your files are in htdocs/project3/, use this:
const API_BASE = 'http://localhost/project3/backend/api.php';

// If you are using a subfolder, adjust accordingly.
// Example: const API_BASE = 'http://localhost/your-folder/backend/api.php';

// ──────────────────────────────────────────────────────────────
//  API HELPERS (Modified for ?resource= syntax)
// ──────────────────────────────────────────────────────────────

async function apiFetch(resource, options = {}) {
    const url = `${API_BASE}?resource=${resource}${options.id ? `&id=${options.id}` : ''}`;
    const fetchOptions = {
        method: options.method || 'GET',
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
    };
    if (options.body) {
        fetchOptions.body = JSON.stringify(options.body);
    }

    const response = await fetch(url, fetchOptions);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || `HTTP ${response.status}`);
    }
    return data;
}

// ──────────────────────────────────────────────────────────────
//  STATE (same as before)
// ──────────────────────────────────────────────────────────────

let customers = [];
let orders = [];
let editingId = null;
let modalMode = 'customer';

// ──────────────────────────────────────────────────────────────
//  DOM REFS
// ──────────────────────────────────────────────────────────────

const $ = (sel) => document.querySelector(sel);
const $$ = (sel) => document.querySelectorAll(sel);

const customerTbody = $('#customerTableBody');
const orderTbody = $('#orderTableBody');
const modalOverlay = $('#modalOverlay');
const modalTitle = $('#modalTitle');
const modalFields = $('#modalFields');
const modalForm = $('#modalForm');
const submitBtn = $('#submitModal');
const cancelBtn = $('#cancelModal');
const closeBtn = $('#closeModal');
const openCustomerBtn = $('#openCustomerModal');
const openOrderBtn = $('#openOrderModal');
const toastContainer = $('#toastContainer');

// ──────────────────────────────────────────────────────────────
//  TOAST SYSTEM
// ──────────────────────────────────────────────────────────────

function showToast(message, type = 'info') {
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.textContent = message;
    toastContainer.appendChild(el);
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateX(30px)';
        setTimeout(() => el.remove(), 300);
    }, 3200);
}

// ──────────────────────────────────────────────────────────────
//  FETCH DATA
// ──────────────────────────────────────────────────────────────

async function loadCustomers() {
    try {
        customers = await apiFetch('customers');
        renderCustomers();
    } catch (err) {
        showToast('Failed to load customers: ' + err.message, 'error');
        customerTbody.innerHTML = `<tr><td colspan="6" class="loading">⚠️ ${err.message}</td></tr>`;
    }
}

async function loadOrders() {
    try {
        orders = await apiFetch('orders');
        renderOrders();
    } catch (err) {
        showToast('Failed to load orders: ' + err.message, 'error');
        orderTbody.innerHTML = `<tr><td colspan="8" class="loading">⚠️ ${err.message}</td></tr>`;
    }
}

async function loadAll() {
    await Promise.all([loadCustomers(), loadOrders()]);
}

// ──────────────────────────────────────────────────────────────
//  RENDER: CUSTOMERS
// ──────────────────────────────────────────────────────────────

function renderCustomers() {
    if (!customers.length) {
        customerTbody.innerHTML = `<tr><td colspan="6" class="loading">No customers yet.</td></tr>`;
        return;
    }
    customerTbody.innerHTML = customers.map(c => {
        const orderCount = orders.filter(o => o.customer_id === c.id).length;
        return `
            <tr>
                <td><strong>${c.id}</strong></td>
                <td>${esc(c.name)}</td>
                <td>${esc(c.email)}</td>
                <td>${esc(c.phone || '—')}</td>
                <td>${orderCount}</td>
                <td>
                    <div class="actions-cell">
                        <button class="btn primary sm" onclick="editCustomer(${c.id})">Edit</button>
                        <button class="btn danger sm" onclick="deleteCustomer(${c.id})">Delete</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// ──────────────────────────────────────────────────────────────
//  RENDER: ORDERS
// ──────────────────────────────────────────────────────────────

function renderOrders() {
    if (!orders.length) {
        orderTbody.innerHTML = `<tr><td colspan="8" class="loading">No orders yet.</td></tr>`;
        return;
    }
    orderTbody.innerHTML = orders.map(o => {
        const total = (o.quantity || 0) * (o.price || 0);
        const customerName = o.customer_name || `#${o.customer_id}`;
        return `
            <tr>
                <td><strong>${o.id}</strong></td>
                <td>${esc(o.product)}</td>
                <td>${o.quantity}</td>
                <td>$${Number(o.price).toFixed(2)}</td>
                <td>$${total.toFixed(2)}</td>
                <td><span class="status-badge ${o.status}">${esc(o.status)}</span></td>
                <td>${esc(customerName)}</td>
                <td>
                    <div class="actions-cell">
                        <button class="btn primary sm" onclick="editOrder(${o.id})">Edit</button>
                        <button class="btn danger sm" onclick="deleteOrder(${o.id})">Delete</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// ──────────────────────────────────────────────────────────────
//  ESCAPE
// ──────────────────────────────────────────────────────────────

function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// ──────────────────────────────────────────────────────────────
//  CRUD: CUSTOMERS
// ──────────────────────────────────────────────────────────────

async function deleteCustomer(id) {
    if (!confirm('Delete this customer and all their orders?')) return;
    try {
        await apiFetch('customers', { method: 'DELETE', id });
        showToast('Customer deleted.', 'success');
        await loadAll();
    } catch (err) {
        showToast('Delete failed: ' + err.message, 'error');
    }
}

function editCustomer(id) {
    const c = customers.find(x => x.id === id);
    if (!c) return;
    openModal('customer', c);
}

// ──────────────────────────────────────────────────────────────
//  CRUD: ORDERS
// ──────────────────────────────────────────────────────────────

async function deleteOrder(id) {
    if (!confirm('Delete this order?')) return;
    try {
        await apiFetch('orders', { method: 'DELETE', id });
        showToast('Order deleted.', 'success');
        await loadAll();
    } catch (err) {
        showToast('Delete failed: ' + err.message, 'error');
    }
}

function editOrder(id) {
    const o = orders.find(x => x.id === id);
    if (!o) return;
    openModal('order', o);
}

// ──────────────────────────────────────────────────────────────
//  MODAL LOGIC (identical to before)
// ──────────────────────────────────────────────────────────────

function openModal(mode, data = null) {
    modalMode = mode;
    editingId = data ? data.id : null;

    const isEdit = !!editingId;
    modalTitle.textContent = isEdit
        ? (mode === 'customer' ? 'Edit Customer' : 'Edit Order')
        : (mode === 'customer' ? 'New Customer' : 'New Order');

    let html = '';
    if (mode === 'customer') {
        html = `
            <div class="form-group">
                <label for="c_name">Full Name *</label>
                <input type="text" id="c_name" value="${esc(data?.name || '')}" required />
            </div>
            <div class="form-group">
                <label for="c_email">Email *</label>
                <input type="email" id="c_email" value="${esc(data?.email || '')}" required />
            </div>
            <div class="form-group">
                <label for="c_phone">Phone</label>
                <input type="text" id="c_phone" value="${esc(data?.phone || '')}" />
            </div>
        `;
    } else {
        const customerOptions = customers.map(c =>
            `<option value="${c.id}" ${data?.customer_id === c.id ? 'selected' : ''}>${esc(c.name)}</option>`
        ).join('');
        html = `
            <div class="form-group">
                <label for="o_customer">Customer *</label>
                <select id="o_customer" required>
                    <option value="">— Select —</option>
                    ${customerOptions}
                </select>
            </div>
            <div class="form-group">
                <label for="o_product">Product *</label>
                <input type="text" id="o_product" value="${esc(data?.product || '')}" required />
            </div>
            <div class="form-group">
                <label for="o_quantity">Quantity *</label>
                <input type="number" id="o_quantity" min="1" value="${data?.quantity || 1}" required />
            </div>
            <div class="form-group">
                <label for="o_price">Price ($) *</label>
                <input type="number" id="o_price" step="0.01" min="0" value="${data?.price || ''}" required />
            </div>
            <div class="form-group">
                <label for="o_status">Status</label>
                <select id="o_status">
                    <option value="pending" ${data?.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="processing" ${data?.status === 'processing' ? 'selected' : ''}>Processing</option>
                    <option value="shipped" ${data?.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                    <option value="delivered" ${data?.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                    <option value="cancelled" ${data?.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
            </div>
        `;
    }

    modalFields.innerHTML = html;
    modalOverlay.classList.add('active');
}

function closeModal() {
    modalOverlay.classList.remove('active');
    editingId = null;
    modalForm.reset();
}

// ──────────────────────────────────────────────────────────────
//  SUBMIT HANDLER
// ──────────────────────────────────────────────────────────────

modalForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const isEdit = !!editingId;

    try {
        if (modalMode === 'customer') {
            const name = document.getElementById('c_name').value.trim();
            const email = document.getElementById('c_email').value.trim();
            const phone = document.getElementById('c_phone').value.trim();
            if (!name || !email) {
                showToast('Name and email are required.', 'error');
                return;
            }
            const payload = { name, email, phone: phone || null };

            if (isEdit) {
                await apiFetch('customers', { method: 'PUT', id: editingId, body: payload });
                showToast('Customer updated.', 'success');
            } else {
                await apiFetch('customers', { method: 'POST', body: payload });
                showToast('Customer created.', 'success');
            }
        } else {
            const customer_id = parseInt(document.getElementById('o_customer').value);
            const product = document.getElementById('o_product').value.trim();
            const quantity = parseInt(document.getElementById('o_quantity').value);
            const price = parseFloat(document.getElementById('o_price').value);
            const status = document.getElementById('o_status').value;

            if (!customer_id || !product || !quantity || isNaN(price)) {
                showToast('Please fill all required fields.', 'error');
                return;
            }
            const payload = { customer_id, product, quantity, price, status };

            if (isEdit) {
                await apiFetch('orders', { method: 'PUT', id: editingId, body: payload });
                showToast('Order updated.', 'success');
            } else {
                await apiFetch('orders', { method: 'POST', body: payload });
                showToast('Order created.', 'success');
            }
        }

        closeModal();
        await loadAll();
    } catch (err) {
        showToast('Error: ' + err.message, 'error');
    }
});

// ──────────────────────────────────────────────────────────────
//  EVENT LISTENERS
// ──────────────────────────────────────────────────────────────

openCustomerBtn.addEventListener('click', () => openModal('customer'));
openOrderBtn.addEventListener('click', () => openModal('order'));
cancelBtn.addEventListener('click', closeModal);
closeBtn.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});

// ──────────────────────────────────────────────────────────────
//  INIT
// ──────────────────────────────────────────────────────────────

loadAll();