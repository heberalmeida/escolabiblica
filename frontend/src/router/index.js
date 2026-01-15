import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

import Catalog from '@/pages/Catalog.vue'
import Checkout from '@/pages/Checkout.vue'
import OrdersByCpf from '@/pages/OrdersByCpf.vue'
import Cart from '@/pages/Cart.vue'
import Payment from '@/pages/Payment.vue'

import Login from '@/pages/admin/Login.vue'
import AdminLayout from '@/pages/admin/AdminLayout.vue'
import Dashboard from '@/pages/admin/Dashboard.vue'
import Orders from '@/pages/admin/Orders.vue'
import ProductsList from '@/pages/admin/ProductsList.vue'
import ProductForm from '@/pages/admin/ProductForm.vue'
import EventsList from '@/pages/admin/EventsList.vue'
import EventForm from '@/pages/admin/EventForm.vue'
import QRCodeValidator from '@/pages/admin/QRCodeValidator.vue'
import Events from '@/pages/Events.vue'
import EventRegistration from '@/pages/EventRegistration.vue'
import RegistrationSuccess from '@/pages/RegistrationSuccess.vue'
import RegistrationsByCpf from '@/pages/RegistrationsByCpf.vue'
import EventPayment from '@/pages/EventPayment.vue'

const routes = [
  { path: '/', name: 'catalog', component: Catalog },
  { path: '/checkout', name: 'checkout', component: Checkout },
  { path: '/orders', name: 'orders', component: OrdersByCpf },
  { path: '/cart', name: 'cart', component: Cart },
  { path: '/orders/:orderNumber', name: 'payment', component: Payment, props: true },
  { path: '/events', name: 'events', component: Events },
  { path: '/events/:id/register', name: 'event-registration', component: EventRegistration, props: true },
  { path: '/registration-success', name: 'registration-success', component: RegistrationSuccess },
  { path: '/registrations', name: 'registrations', component: RegistrationsByCpf },
  { path: '/event-payment', name: 'event-payment', component: EventPayment },

  { path: '/admin/login', name: 'admin-login', component: Login },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'admin-dashboard', component: Dashboard },
      { path: 'orders', name: 'admin-orders', component: Orders },
      { path: 'products', name: 'admin-products', component: ProductsList },
      { path: 'products/:id', name: 'admin-product-form', component: ProductForm, props: true },
      { path: 'events', name: 'admin-events', component: EventsList },
      { path: 'events/new', name: 'admin-event-new', component: EventForm },
      { path: 'events/:id', name: 'admin-event-form', component: EventForm, props: true },
      { path: 'registrations', name: 'admin-registrations', component: () => import('@/pages/admin/RegistrationsList.vue') },
      { path: 'validate', name: 'admin-validate', component: QRCodeValidator },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user) {
    try {
      await auth.fetchUser()
    } catch (e) {
      auth.logout()
      return { name: 'admin-login' }
    }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'admin-login' }
  }
})

export default router
