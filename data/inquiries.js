// ============================================================
//  SNEHA ENTERPRISES — INQUIRIES STORAGE
//  In production, replace this with a real backend/database.
//  Inquiries are stored in localStorage for demo purposes.
// ============================================================
var INQUIRIES_KEY = 'sneha_inquiries';

var InquiryStore = {
  getAll: function() {
    try {
      return JSON.parse(localStorage.getItem(INQUIRIES_KEY) || '[]');
    } catch(e) { return []; }
  },
  save: function(inquiry) {
    var all = this.getAll();
    inquiry.id = 'INQ-' + Date.now();
    inquiry.date = new Date().toISOString();
    inquiry.status = 'new';
    all.unshift(inquiry);
    localStorage.setItem(INQUIRIES_KEY, JSON.stringify(all));
    return inquiry.id;
  },
  updateStatus: function(id, status) {
    var all = this.getAll();
    all = all.map(function(i) {
      if (i.id === id) i.status = status;
      return i;
    });
    localStorage.setItem(INQUIRIES_KEY, JSON.stringify(all));
  },
  delete: function(id) {
    var all = this.getAll().filter(function(i) { return i.id !== id; });
    localStorage.setItem(INQUIRIES_KEY, JSON.stringify(all));
  },
  getCount: function(status) {
    var all = this.getAll();
    if (!status) return all.length;
    return all.filter(function(i) { return i.status === status; }).length;
  }
};
