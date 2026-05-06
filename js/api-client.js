// ============================================================
//  SNEHA ENTERPRISES — FRONTEND API CLIENT
//  Used by contact.html and products.html forms
// ============================================================
const SNEHA_API = 'http://localhost:5000/api';

const SnehaAPI = {
  async submitInquiry(data) {
    const res = await fetch(SNEHA_API + '/inquiries', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || 'Submission failed');
    return json;
  },

  async getProducts(params = {}) {
    const q = new URLSearchParams({ active: '1', ...params }).toString();
    const res = await fetch(SNEHA_API + '/products?' + q);
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || 'Failed to load products');
    return json.products;
  }
};
