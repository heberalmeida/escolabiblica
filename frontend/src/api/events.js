import http from './http'

export const eventsApi = {
  list: (params = {}) => http.get('/events', { params }),
  get: (id) => http.get(`/events/${id}`),
  create: (data) => http.post('/admin/events', data),
  update: (id, data) => http.put(`/admin/events/${id}`, data),
  delete: (id) => http.delete(`/admin/events/${id}`),
}

export const registrationsApi = {
  list: (params = {}) => http.get('/admin/registrations', { params }),
  get: (id) => http.get(`/registrations/${id}`),
  create: (data) => http.post('/registrations', data),
  getByQrCode: (qrCode) => http.get(`/registrations/qr/${qrCode}`),
  getByPhone: (phone) => http.get(`/registrations/phone/${phone}`),
  getByCpf: (cpf, params = {}) => http.get(`/registrations/by-cpf/${cpf}`, { params }),
  markAsPaid: (id) => http.put(`/admin/registrations/${id}/mark-as-paid`),
}

export const validationApi = {
  validateByQrCode: (qrCode) => http.post('/validate/qrcode', { qr_code: qrCode }),
  validateByName: (name, eventId) => http.post('/validate/name', { name, event_id: eventId }),
  validateByPhone: (phone, eventId) => http.post('/validate/phone', { phone, event_id: eventId }),
}

export const churchesApi = {
  list: () => http.get('/churches'),
}
