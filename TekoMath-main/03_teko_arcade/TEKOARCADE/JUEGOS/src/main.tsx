import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App'
import './index.css'
import './pages/HomePage.css'

const boot = (window as Window & { __TEKO_ARCADE_BOOT__?: Promise<void> }).__TEKO_ARCADE_BOOT__
Promise.resolve(boot).then(() => createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)).catch((error: Error) => { document.getElementById('root')!.textContent = error.message })
