import './assets/main.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import VueLazyload from "vue3-lazyload";
import App from './App.vue'
import router from './router'
import { vMaska } from 'maska/vue'

import { FontAwesomeIcon } from './plugins/fontawesome'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(VueLazyload, {
  preLoad: 1.3,
  error: "/assets/error.jpg",
  loading: "/assets/images/loading-spin.svg",
  attempt: 10,
});

app.directive('maska', vMaska)

app.component('font-awesome-icon', FontAwesomeIcon)

app.mount('#app')
