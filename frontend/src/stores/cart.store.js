import { defineStore } from 'pinia'

const STORAGE_KEY = 'cart_session'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]')
  }),
  getters: {
    total(state) {
      return state.items.reduce((sum, i) => sum + i.price * i.quantity, 0)
    },
    eventItems(state) {
      return state.items.filter(i => i.type === 'event')
    },
    productItems(state) {
      return state.items.filter(i => i.type === 'product')
    }
  },
  actions: {
    persist() {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(this.items))
    },
    addItem(product, variant, quantity = 1) {
      const existing = this.items.find(i => i.variantId === variant.id && i.type === 'product')
      if (existing) {
        existing.quantity += quantity
      } else {
        this.items.push({
          type: 'product',
          productId: product.id,
          variantId: variant.id,
          name: product.name,
          variantName: `${variant.name} (${variant.color} - ${variant.size})`,
          price: variant.price,
          quantity
        })
      }
      this.persist()
    },
    addEvent(event, quantity = 1) {
      const existing = this.items.find(i => i.eventId === event.id && i.type === 'event')
      if (existing) {
        existing.quantity += quantity
      } else {
        this.items.push({
          type: 'event',
          eventId: event.id,
          name: event.name,
          description: event.description,
          image: event.image,
          start_date: event.start_date,
          end_date: event.end_date,
          price: event.price,
          quantity
        })
      }
      this.persist()
    },
    removeItem(id, type = null) {
      if (type) {
        this.items = this.items.filter(i => !(i.type === type && (i.variantId === id || i.eventId === id)))
      } else {
        // Compatibilidade com código antigo
        this.items = this.items.filter(i => i.variantId !== id)
      }
      this.persist()
    },
    updateQuantity(id, quantity, type = null) {
      let item
      if (type) {
        item = this.items.find(i => i.type === type && (i.variantId === id || i.eventId === id))
      } else {
        // Compatibilidade com código antigo
        item = this.items.find(i => i.variantId === id)
      }
      
      if (item) {
        if (quantity <= 0) {
          this.removeItem(id, type || item.type)
        } else {
          item.quantity = quantity
        }
        this.persist()
      }
    },
    clear() {
      this.items = []
      this.persist()
    }
  }
})
