import { onMounted, onUnmounted } from 'vue'
import { initializeApp, getApps } from 'firebase/app'
import { getDatabase, ref as rtdbRef, onValue, off } from 'firebase/database'

let enabled = import.meta.env.VITE_FIREBASE_ENABLED === 'true'
let prefix  = import.meta.env.VITE_FIREBASE_COLLECTION_PREFIX || ''
let db = null

function ensureDb() {
  if (!enabled) return null
  if (!db) {
    const firebaseConfig = {
      apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
      authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
      projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
      storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
      messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
      appId: import.meta.env.VITE_FIREBASE_APP_ID,
      databaseURL: import.meta.env.VITE_FIREBASE_DATABASE_URL
    }
    const app = getApps().length ? getApps()[0] : initializeApp(firebaseConfig)
    db = getDatabase(app)
  }
  return db
}

/**
 * Observa updates/<prefix><channel> e dispara callback(payload) quando mudar.
 * Retorna uma função para trocar o canal em tempo de execução (rebind).
 */
export function useRtdbWatcher(initialChannel, callback) {
  let currentChannel = initialChannel
  let stop = null

  function bind(channel) {
    const database = ensureDb()
    if (!database) return
    const path = `updates/${prefix}${channel}`
    const node = rtdbRef(database, path)
    const listener = snap => { if (snap.exists()) callback(snap.val()) }
    onValue(node, listener)
    stop = () => off(node, 'value', listener)
  }

  onMounted(() => { if (enabled) bind(currentChannel) })
  onUnmounted(() => { if (stop) stop() })

  function rebind(newChannel) {
    if (stop) stop()
    currentChannel = newChannel
    bind(currentChannel)
  }

  return { rebind }
}
